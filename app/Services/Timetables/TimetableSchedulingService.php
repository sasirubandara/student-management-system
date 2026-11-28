<?php

namespace App\Services\Timetables;

use App\Models\AcademicClass;
use App\Models\AcademicTerm;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TimetableSchedulingService
{
    public function __construct(
        private readonly TimetableTransaction $transaction,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * Create a recurring weekly timetable entry.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        array $attributes,
        User $actor
    ): TimetableEntry {
        $this->authorizeAdministrator($actor);

        return $this->transaction->run(function () use (
            $attributes,
            $actor
        ): TimetableEntry {
            $validated = Validator::make($attributes, [
                'academic_term_id' => [
                    'required',
                    'integer',
                    'exists:academic_terms,id',
                ],
                'teaching_assignment_id' => [
                    'required',
                    'integer',
                    'exists:teaching_assignments,id',
                ],
                'day_of_week' => [
                    'required',
                    'integer',
                    'between:1,7',
                ],
                'start_time' => [
                    'required',
                    'date_format:H:i',
                ],
                'end_time' => [
                    'required',
                    'date_format:H:i',
                    'after:start_time',
                ],
            ])->validate();

            $term = AcademicTerm::query()
                ->with('academicYear')
                ->findOrFail($validated['academic_term_id']);

            $assignment = TeachingAssignment::query()
                ->with([
                    'teacher',
                    'academicClass',
                    'subject',
                ])
                ->findOrFail(
                    $validated['teaching_assignment_id']
                );

            $this->validateAssignment($term, $assignment);

            $startTime = $validated['start_time'].':00';
            $endTime = $validated['end_time'].':00';

            $this->assertNoConflict(
                $term->id,
                $assignment,
                (int) $validated['day_of_week'],
                $startTime,
                $endTime
            );

            $entry = TimetableEntry::query()->create([
                'academic_term_id' => $term->id,
                'teaching_assignment_id' => $assignment->id,
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->record(
                event: 'timetable.entry.created',
                auditable: $entry,
                properties: [
                    'academic_term_id' => $term->id,
                    'teaching_assignment_id' => $assignment->id,
                    'day_of_week' => $entry->day_of_week,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                actor: $actor
            );

            return $entry;
        });
    }

    /**
     * Update an existing recurring weekly timetable entry.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        TimetableEntry $entry,
        array $attributes,
        User $actor
    ): TimetableEntry {
        $this->authorizeAdministrator($actor);

        return $this->transaction->run(function () use (
            $entry,
            $attributes,
            $actor
        ): TimetableEntry {
            // Reload the entry after acquiring the shared timetable lock.
            $lockedEntry = TimetableEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            $validated = Validator::make($attributes, [
                'academic_term_id' => [
                    'required',
                    'integer',
                    'exists:academic_terms,id',
                ],
                'teaching_assignment_id' => [
                    'required',
                    'integer',
                    'exists:teaching_assignments,id',
                ],
                'day_of_week' => [
                    'required',
                    'integer',
                    'between:1,7',
                ],
                'start_time' => [
                    'required',
                    'date_format:H:i',
                ],
                'end_time' => [
                    'required',
                    'date_format:H:i',
                    'after:start_time',
                ],
            ])->validate();

            $term = AcademicTerm::query()
                ->with('academicYear')
                ->findOrFail($validated['academic_term_id']);

            $assignment = TeachingAssignment::query()
                ->with([
                    'teacher',
                    'academicClass',
                    'subject',
                ])
                ->findOrFail($validated['teaching_assignment_id']);

            $this->validateAssignment($term, $assignment);

            $startTime = $validated['start_time'].':00';
            $endTime = $validated['end_time'].':00';

            $this->assertNoConflict(
                $term->id,
                $assignment,
                (int) $validated['day_of_week'],
                $startTime,
                $endTime,
                $lockedEntry->id
            );

            $lockedEntry->fill([
                'academic_term_id' => $term->id,
                'teaching_assignment_id' => $assignment->id,
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            $changedFields = array_keys($lockedEntry->getDirty());

            // An unchanged timetable must not generate an update audit.
            if ($changedFields === []) {
                return $lockedEntry;
            }

            $changes = [];

            foreach ($changedFields as $field) {
                $changes[$field] = [
                    'old' => $lockedEntry->getOriginal($field),
                    'new' => $lockedEntry->{$field},
                ];
            }

            $lockedEntry->save();

            $this->auditLogger->record(
                event: 'timetable.entry.updated',
                auditable: $lockedEntry,
                properties: [
                    'changed_fields' => $changedFields,
                    'changes' => $changes,
                ],
                actor: $actor
            );

            return $lockedEntry;
        });
    }

    /**
     * Delete a timetable entry with transactional audit logging.
     */
    public function delete(
        TimetableEntry $entry,
        User $actor
    ): void {
        $this->authorizeAdministrator($actor);

        $this->transaction->run(function () use (
            $entry,
            $actor
        ): void {
            $lockedEntry = TimetableEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            $auditProperties = [
                'academic_term_id' => $lockedEntry->academic_term_id,
                'teaching_assignment_id' => $lockedEntry->teaching_assignment_id,
                'day_of_week' => $lockedEntry->day_of_week,
                'start_time' => $lockedEntry->start_time,
                'end_time' => $lockedEntry->end_time,
                'changed_fields' => ['deleted'],
            ];

            $lockedEntry->delete();

            $this->auditLogger->record(
                event: 'timetable.entry.deleted',
                auditable: $lockedEntry,
                properties: $auditProperties,
                actor: $actor
            );
        });
    }

    /**
     * Require an active administrator.
     */
    private function authorizeAdministrator(User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException(
                'You are not authorized to manage timetables.'
            );
        }
    }

    /**
     * Validate the teaching assignment and academic year.
     */
    private function validateAssignment(
        AcademicTerm $term,
        TeachingAssignment $assignment
    ): void {
        $teacher = $assignment->teacher;
        $academicClass = $assignment->academicClass;
        $subject = $assignment->subject;

        if (
            ! $teacher
            || $teacher->trashed()
            || $teacher->status !== Teacher::STATUS_ACTIVE
        ) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'The assigned teacher must be active.',
            ]);
        }

        if (
            ! $academicClass
            || $academicClass->trashed()
            || $academicClass->status !== AcademicClass::STATUS_ACTIVE
        ) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'The assigned academic class must be active.',
            ]);
        }

        if (
            ! $subject
            || $subject->trashed()
            || $subject->status !== Subject::STATUS_ACTIVE
        ) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'The assigned subject must be active.',
            ]);
        }

        if (
            ! $term->academicYear
            || $academicClass->academic_year !== $term->academicYear->year
        ) {
            throw ValidationException::withMessages([
                'academic_term_id' => 'The academic term must match the class academic year.',
            ]);
        }

        $subjectAssigned = DB::table('academic_class_subject')
            ->where('academic_class_id', $academicClass->id)
            ->where('subject_id', $subject->id)
            ->exists();

        if (! $subjectAssigned) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'The subject must be assigned to the academic class.',
            ]);
        }
    }

    /**
     * Reject overlapping teacher or academic class periods.
     *
     * Exclude the current timetable entry during updates.
     */
    private function assertNoConflict(
        int $termId,
        TeachingAssignment $assignment,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $exceptEntryId = null
    ): void {
        $conflict = TimetableEntry::query()
            ->where('academic_term_id', $termId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when(
                $exceptEntryId !== null,
                fn (Builder $query) => $query->where(
                    'id',
                    '!=',
                    $exceptEntryId
                )
            )
            ->whereHas(
                'teachingAssignment',
                function (Builder $query) use ($assignment): void {
                    $query->where(function (Builder $query) use (
                        $assignment
                    ): void {
                        $query
                            ->where(
                                'teacher_id',
                                $assignment->teacher_id
                            )
                            ->orWhere(
                                'academic_class_id',
                                $assignment->academic_class_id
                            );
                    });
                }
            )
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected period conflicts with an existing '
                    .'teacher or academic class timetable entry.',
            ]);
        }
    }
}
