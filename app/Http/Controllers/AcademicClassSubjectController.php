<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AcademicClassSubjectController extends Controller
{
    /**
     * Assign subjects to an academic class.
     */
    public function store(
        Request $request,
        AcademicClass $academicClass,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $academicClass);

        $validated = $request->validate([
            'subject_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'subject_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('subjects', 'id')
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],
        ]);

        DB::transaction(function () use (
            $academicClass,
            $validated,
            $auditLogger
        ): void {
            $changes = $academicClass->subjects()->syncWithoutDetaching(
                $validated['subject_ids']
            );

            foreach ($changes['attached'] as $subjectId) {
                $auditLogger->record(
                    'academic_class.subject_assigned',
                    $academicClass,
                    [
                        'subject_id' => (int) $subjectId,
                    ]
                );
            }
        });

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Subjects assigned successfully.');
    }

    /**
     * Remove a subject assignment from an academic class.
     */
    public function destroy(
        AcademicClass $academicClass,
        Subject $subject,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $academicClass);

        DB::transaction(function () use (
            $academicClass,
            $subject,
            $auditLogger
        ): void {
            $exists = $academicClass->subjects()
                ->whereKey($subject->id)
                ->exists();

            abort_unless($exists, 404);

            $academicClass->subjects()->detach($subject->id);

            $auditLogger->record(
                'academic_class.subject_removed',
                $academicClass,
                [
                    'subject_id' => $subject->getKey(),
                ]
            );
        });

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Subject removed from the class successfully.');
    }
}