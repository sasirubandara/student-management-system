<?php

namespace App\Services\StudentPromotions;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentPromotionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * Promote a student while preserving the previous enrollment.
     */
    public function promote(
        StudentEnrollment $sourceEnrollment,
        AcademicClass $destinationClass,
        string $promotionType,
        string $promotionDate,
        ?string $notes,
        User $actor
    ): StudentPromotion {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException(
                'You are not authorized to promote students.'
            );
        }

        $validated = Validator::make([
            'promotion_type' => $promotionType,
            'promotion_date' => $promotionDate,
            'notes' => $notes,
        ], [
            'promotion_type' => [
                'required',
                Rule::in([
                    StudentPromotion::TYPE_ADVANCED,
                    StudentPromotion::TYPE_REPEATED,
                ]),
            ],
            'promotion_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ])->validate();

        return DB::transaction(function () use (
            $sourceEnrollment,
            $destinationClass,
            $validated,
            $actor
        ): StudentPromotion {
            // Lock the student first for a consistent lock order.
            $student = Student::withTrashed()
                ->whereKey($sourceEnrollment->student_id)
                ->lockForUpdate()
                ->first();

            if (
                ! $student
                || $student->trashed()
                || $student->status !== Student::STATUS_ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'student_id' => 'The student must be active.',
                ]);
            }

            // Reload the enrollment inside the transaction.
            $source = StudentEnrollment::query()
                ->whereKey($sourceEnrollment->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $source
                || $source->status !== StudentEnrollment::STATUS_ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'source_enrollment_id' => 'The source enrollment must be active.',
                ]);
            }

            $sourceClass = $source->academicClass;

            if (
                ! $sourceClass
                || $sourceClass->trashed()
                || $sourceClass->status !== AcademicClass::STATUS_ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'source_enrollment_id' => 'The source academic class must be active.',
                ]);
            }

            $destination = AcademicClass::withTrashed()
                ->whereKey($destinationClass->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $destination
                || $destination->trashed()
                || $destination->status !== AcademicClass::STATUS_ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'Select an active destination class.',
                ]);
            }

            if ($sourceClass->id === $destination->id) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'The destination must differ from the source class.',
                ]);
            }

            $academicYear = AcademicYear::query()
                ->where('year', $destination->academic_year)
                ->first();

            if (! $academicYear) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'The destination academic year is not registered.',
                ]);
            }

            if (
                $destination->academic_year <=
                $sourceClass->academic_year
            ) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'The destination must be in a later academic year.',
                ]);
            }

            $expectedGrade = $validated['promotion_type'] ===
                StudentPromotion::TYPE_ADVANCED
                    ? $sourceClass->grade_level + 1
                    : $sourceClass->grade_level;

            if ($destination->grade_level !== $expectedGrade) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'The destination grade does not match the progression type.',
                ]);
            }

            $date = CarbonImmutable::parse(
                $validated['promotion_date']
            );

            if (
                $date->lt($academicYear->start_date)
                || $date->gt($academicYear->end_date)
                || $date->lt($source->enrollment_date)
            ) {
                throw ValidationException::withMessages([
                    'promotion_date' => 'The promotion date is outside the permitted range.',
                ]);
            }

            // A class can have only one enrollment per student,
            // regardless of enrollment status.
            $destinationAlreadyExists = StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_class_id', $destination->id)
                ->exists();

            if ($destinationAlreadyExists) {
                throw ValidationException::withMessages([
                    'destination_class_id' => 'The student already has an enrollment in this class.',
                ]);
            }

            // Reject conflicting active enrollments.
            $activeEnrollments = StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('status', StudentEnrollment::STATUS_ACTIVE)
                ->lockForUpdate()
                ->get();

            if (
                $activeEnrollments->count() !== 1
                || $activeEnrollments->first()->id !== $source->id
            ) {
                throw ValidationException::withMessages([
                    'source_enrollment_id' => 'The student must have exactly one active enrollment.',
                ]);
            }

            if (
                StudentPromotion::query()
                    ->where('source_enrollment_id', $source->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'source_enrollment_id' => 'This enrollment has already been promoted.',
                ]);
            }

            // Complete the old enrollment without changing its class.
            $source->update([
                'status' => StudentEnrollment::STATUS_COMPLETED,
            ]);

            // Create an entirely new enrollment.
            $newEnrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_class_id' => $destination->id,
                'enrollment_date' => $date->toDateString(),
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'notes' => null,
            ]);

            // Record the link between the two enrollments.
            $promotion = StudentPromotion::create([
                'student_id' => $student->id,
                'source_enrollment_id' => $source->id,
                'destination_enrollment_id' => $newEnrollment->id,
                'promotion_type' => $validated['promotion_type'],
                'promotion_date' => $date->toDateString(),
                'notes' => $validated['notes'],
                'promoted_by' => $actor->id,
            ]);

            // Audit in the same transaction.
            $this->auditLogger->record(
                event: 'student.promotion.created',
                auditable: $promotion,
                properties: [
                    'student_id' => $student->id,
                    'source_enrollment_id' => $source->id,
                    'destination_enrollment_id' => $newEnrollment->id,
                    'promotion_type' => $promotion->promotion_type,
                ],
                actor: $actor
            );

            return $promotion;
        }, 3);
    }
}
