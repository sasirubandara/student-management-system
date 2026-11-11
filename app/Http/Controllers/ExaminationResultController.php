<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\GradeCalculator;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExaminationResultController extends Controller
{
    /**
     * Record or update results for students in an examination.
     */
    public function save(
        Request $request,
        Examination $examination,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $examination);

        $validated = $request->validate([
            'results' => [
                'required',
                'array',
                'min:1',
            ],

            'results.*.student_enrollment_id' => [
                'required',
                'integer',
                'distinct',
                'exists:student_enrollments,id',
            ],

            'results.*.marks_obtained' => [
                'required',
                'numeric',
                'min:0',
                'max:' . $examination->maximum_marks,
            ],

            'results.*.remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $teachingAssignment = $examination->teachingAssignment;

        $enrollmentIds = collect($validated['results'])
            ->pluck('student_enrollment_id')
            ->all();

        /*
         * Only accept active enrollments in the examination's class.
         * Archived or inactive students cannot receive new results.
         */
        $enrollments = StudentEnrollment::query()
            ->whereIn('id', $enrollmentIds)
            ->where(
                'academic_class_id',
                $teachingAssignment->academic_class_id
            )
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', function ($query): void {
                $query->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->get()
            ->keyBy('id');

        if ($enrollments->count() !== count($enrollmentIds)) {
            throw ValidationException::withMessages([
                'results' =>
                    'One or more students are inactive, archived, not actively enrolled, or do not belong to this examination class.',
            ]);
        }

        DB::transaction(function () use (
            $examination,
            $validated,
            $enrollments,
            $auditLogger
        ): void {
            $calculator = app(GradeCalculator::class);

            foreach ($validated['results'] as $resultData) {
                $enrollment = $enrollments->get(
                    $resultData['student_enrollment_id']
                );

                $result = $examination->results()
                    ->firstOrNew([
                        'student_enrollment_id' => $enrollment->id,
                    ]);

                if ($result->exists) {
                    Gate::authorize('update', $result);
                } else {
                    Gate::authorize(
                        'create',
                        ExaminationResult::class
                    );
                }

                $isNew = ! $result->exists;

                $previousMarks = $result->marks_obtained;
                $previousGrade = $result->grade;

                $result->fill([
                    'marks_obtained' => $resultData['marks_obtained'],

                    'grade' => $calculator->calculate(
                        $resultData['marks_obtained'],
                        $examination->maximum_marks
                    ),

                    'remarks' => $resultData['remarks'] ?? null,
                ]);

                $changedFields = $isNew
                    ? [
                        'marks_obtained',
                        'grade',
                        ...(
                            array_key_exists('remarks', $resultData)
                                ? ['remarks']
                                : []
                        ),
                    ]
                    : array_keys($result->getDirty());

                // Skip existing results when nothing has changed.
                if (! $isNew && $changedFields === []) {
                    continue;
                }

                $examination->results()->save($result);

                $properties = [
                    'examination_id' => $examination->getKey(),
                    'student_enrollment_id' => $enrollment->getKey(),
                    'changed_fields' => $changedFields,
                ];

                if ($isNew) {
                    // Record initial academic result values.
                    $properties['values'] = [
                        'marks_obtained' => $result->marks_obtained,
                        'grade' => $result->grade,
                    ];
                } else {
                    $changes = [];

                    if (in_array('marks_obtained', $changedFields, true)) {
                        $changes['marks_obtained'] = [
                            'old' => $previousMarks,
                            'new' => $result->marks_obtained,
                        ];
                    }

                    if (in_array('grade', $changedFields, true)) {
                        $changes['grade'] = [
                            'old' => $previousGrade,
                            'new' => $result->grade,
                        ];
                    }

                    if ($changes !== []) {
                        $properties['changes'] = $changes;
                    }
                }

                $auditLogger->record(
                    $isNew
                        ? 'examination.result_created'
                        : 'examination.result_updated',
                    $result,
                    $properties
                );
            }
        });

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination results saved successfully.');
    }

    /**
     * Remove an individual examination result.
     */
    public function destroy(
        ExaminationResult $examinationResult,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $examinationResult);

        $examination = $examinationResult->examination;

        DB::transaction(function () use (
            $examinationResult,
            $auditLogger
        ): void {
            $lockedResult = ExaminationResult::query()
                ->whereKey($examinationResult->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $auditProperties = [
                'examination_id' => $lockedResult->examination_id,
                'student_enrollment_id' => $lockedResult->student_enrollment_id,
                'values' => [
                    'marks_obtained' => $lockedResult->marks_obtained,
                    'grade' => $lockedResult->grade,
                ],
                'changed_fields' => ['deleted'],
            ];

            $lockedResult->delete();

            $auditLogger->record(
                'examination.result_deleted',
                $lockedResult,
                $auditProperties
            );
        });

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination result removed successfully.');
    }
}