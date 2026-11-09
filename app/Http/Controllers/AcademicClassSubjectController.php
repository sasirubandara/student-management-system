<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AcademicClassSubjectController extends Controller
{
    /**
     * Assign subjects to an academic class.
     */
    public function store(
        Request $request,
        AcademicClass $academicClass
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

        $academicClass->subjects()->syncWithoutDetaching(
            $validated['subject_ids']
        );

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Subjects assigned successfully.');
    }

    /**
     * Remove a subject assignment from an academic class.
     */
    public function destroy(
        AcademicClass $academicClass,
        Subject $subject
    ): RedirectResponse {
        Gate::authorize('update', $academicClass);

        abort_unless(
            $academicClass->subjects()
                ->whereKey($subject->id)
                ->exists(),
            404
        );

        $academicClass->subjects()->detach($subject->id);

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Subject removed from the class successfully.');
    }
}