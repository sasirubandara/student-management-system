<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    /**
     * Display active teachers with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $search = trim((string) $request->query('search', ''));

        $teachers = Teacher::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('teacher_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', compact('teachers', 'search'));
    }

    /**
     * Display archived teachers.
     */
    public function archived(): View
    {
        Gate::authorize('viewArchived', Teacher::class);

        $teachers = Teacher::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(10);

        return view('teachers.archived', compact('teachers'));
    }

    /**
     * Restore an archived teacher.
     */
    public function restore(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('restore', $teacher);

        abort_unless($teacher->trashed(), 404);

        $teacher->restore();

        return to_route('teachers.show', $teacher)
            ->with('success', 'Teacher restored successfully.');
    }

    /**
     * Display an individual teacher profile.
     */
    public function show(Teacher $teacher): View
    {
        Gate::authorize('view', $teacher);

        return view('teachers.show', compact('teacher'));
    }

    /**
     * Display the teacher editing form.
     */
    public function edit(Teacher $teacher): View
    {
        Gate::authorize('update', $teacher);

        return view('teachers.edit', compact('teacher'));
    }

    /**
     * Update an existing teacher's information.
     */
    public function update(
        Request $request,
        Teacher $teacher
    ): RedirectResponse {
        Gate::authorize('update', $teacher);

        $validated = $request->validate([
            'teacher_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('teachers', 'teacher_number')
                    ->ignore($teacher->id),
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'employment_date' => ['required', 'date'],
            'status' => [
                'required',
                Rule::in([
                    Teacher::STATUS_ACTIVE,
                    Teacher::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $teacher->update($validated);

        return to_route('teachers.show', $teacher)
            ->with('success', 'Teacher information updated successfully.');
    }

    /**
     * Archive a teacher using soft deletion.
     */
    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $teacher->delete();

        return to_route('teachers.index')
            ->with('success', 'Teacher archived successfully.');
    }

    /**
     * Display the teacher registration form.
     */
    public function create(): View
    {
        Gate::authorize('create', Teacher::class);

        return view('teachers.create');
    }

    /**
     * Store a newly registered teacher.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $validated = $request->validate([
            'teacher_number' => [
                'required',
                'string',
                'max:20',
                'unique:teachers,teacher_number',
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'employment_date' => ['required', 'date'],
            'status' => [
                'required',
                Rule::in([
                    Teacher::STATUS_ACTIVE,
                    Teacher::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $teacher = Teacher::create($validated);

        return to_route('teachers.show', $teacher)
            ->with('success', 'Teacher registered successfully.');
    }
}