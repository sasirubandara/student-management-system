<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\GradeCalculator;
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
        Examination $examination
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
            $enrollments
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

                $result->fill([
                    'marks_obtained' => $resultData['marks_obtained'],

                    'grade' => $calculator->calculate(
                        $resultData['marks_obtained'],
                        $examination->maximum_marks
                    ),

                    'remarks' => $resultData['remarks'] ?? null,
                ]);

                $examination->results()->save($result);
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
        ExaminationResult $examinationResult
    ): RedirectResponse {
        Gate::authorize('delete', $examinationResult);

        $examination = $examinationResult->examination;

        $examinationResult->delete();

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination result removed successfully.');
    }
}