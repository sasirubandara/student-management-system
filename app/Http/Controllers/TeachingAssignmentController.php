<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeachingAssignmentController extends Controller
{
    /**
     * Display teaching assignments with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TeachingAssignment::class);

        $search = trim((string) $request->query('search', ''));

        $assignments = TeachingAssignment::query()
            ->with(['teacher', 'academicClass', 'subject'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->whereHas('teacher', function (Builder $query) use ($term): void {
                        $query->where('teacher_number', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    })
                    ->orWhereHas('academicClass', function (Builder $query) use ($term): void {
                        $query->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('subject', function (Builder $query) use ($term): void {
                        $query->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teaching-assignments.index', compact(
            'assignments',
            'search'
        ));
    }

    /**
     * Display the teaching assignment creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', TeachingAssignment::class);

        $teachers = Teacher::query()
            ->where('status', Teacher::STATUS_ACTIVE)
            ->orderBy('teacher_number')
            ->get();

        $academicClasses = AcademicClass::query()
            ->where('status', AcademicClass::STATUS_ACTIVE)
            ->with([
                'subjects' => function (BelongsToMany $query): void {
                    $query->where('status', Subject::STATUS_ACTIVE)
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        return view('teaching-assignments.create', compact(
            'teachers',
            'academicClasses'
        ));
    }

    /**
     * Store a new teaching assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', TeachingAssignment::class);

        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id')
                    ->where('status', Teacher::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),

                Rule::unique('teaching_assignments', 'teacher_id')
                    ->where('academic_class_id', $request->input('academic_class_id'))
                    ->where('subject_id', $request->input('subject_id')),
            ],

            'academic_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('status', AcademicClass::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],

            'subject_id' => [
                'required',
                'integer',

                Rule::exists('subjects', 'id')
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),

                Rule::exists('academic_class_subject', 'subject_id')
                    ->where('academic_class_id', $request->input('academic_class_id')),
            ],
        ]);

        $assignment = TeachingAssignment::create($validated);

        return to_route('teaching-assignments.show', $assignment)
            ->with('success', 'Teaching assignment created successfully.');
    }

    /**
     * Display an individual teaching assignment.
     */
    public function show(TeachingAssignment $teachingAssignment): View
    {
        Gate::authorize('view', $teachingAssignment);

        $teachingAssignment->load([
            'teacher',
            'academicClass',
            'subject',
        ]);

        return view('teaching-assignments.show', compact(
            'teachingAssignment'
        ));
    }

    /**
     * Display the teaching assignment editing form.
     */
    public function edit(TeachingAssignment $teachingAssignment): View
    {
        Gate::authorize('update', $teachingAssignment);

        $teachingAssignment->load([
            'teacher',
            'academicClass',
            'subject',
        ]);

        $teachers = Teacher::query()
            ->where('status', Teacher::STATUS_ACTIVE)
            ->orderBy('teacher_number')
            ->get();

        $academicClasses = AcademicClass::query()
            ->where('status', AcademicClass::STATUS_ACTIVE)
            ->with([
                'subjects' => function (BelongsToMany $query): void {
                    $query->where('status', Subject::STATUS_ACTIVE)
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        return view('teaching-assignments.edit', compact(
            'teachingAssignment',
            'teachers',
            'academicClasses'
        ));
    }

    /**
     * Update an existing teaching assignment.
     */
    public function update(
        Request $request,
        TeachingAssignment $teachingAssignment
    ): RedirectResponse {
        Gate::authorize('update', $teachingAssignment);

        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id')
                    ->where('status', Teacher::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),

                Rule::unique('teaching_assignments', 'teacher_id')
                    ->where('academic_class_id', $request->input('academic_class_id'))
                    ->where('subject_id', $request->input('subject_id'))
                    ->ignore($teachingAssignment->id),
            ],

            'academic_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('status', AcademicClass::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],

            'subject_id' => [
                'required',
                'integer',

                Rule::exists('subjects', 'id')
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),

                Rule::exists('academic_class_subject', 'subject_id')
                    ->where('academic_class_id', $request->input('academic_class_id')),
            ],
        ]);

        $teachingAssignment->update($validated);

        return to_route('teaching-assignments.show', $teachingAssignment)
            ->with('success', 'Teaching assignment updated successfully.');
    }

    /**
     * Remove a teaching assignment.
     */
    public function destroy(
        TeachingAssignment $teachingAssignment
    ): RedirectResponse {
        Gate::authorize('delete', $teachingAssignment);

        $teachingAssignment->delete();

        return to_route('teaching-assignments.index')
            ->with('success', 'Teaching assignment removed successfully.');
    }
}