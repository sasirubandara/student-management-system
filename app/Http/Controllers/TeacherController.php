<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    /**
     * Display teachers with optional search and filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    Teacher::STATUS_ACTIVE,
                    Teacher::STATUS_INACTIVE,
                ]),
            ],
            'employed_from' => ['nullable', 'date_format:Y-m-d'],
            'employed_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:employed_from',
            ],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';
        $employedFrom = $validated['employed_from'] ?? '';
        $employedTo = $validated['employed_to'] ?? '';

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
            ->when($status !== '', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($employedFrom !== '', function (Builder $query) use ($employedFrom): void {
                $query->whereDate('employment_date', '>=', $employedFrom);
            })
            ->when($employedTo !== '', function (Builder $query) use ($employedTo): void {
                $query->whereDate('employment_date', '<=', $employedTo);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $hasFilters = $search !== ''
            || $status !== ''
            || $employedFrom !== ''
            || $employedTo !== '';

        return view('teachers.index', compact(
            'teachers',
            'search',
            'status',
            'employedFrom',
            'employedTo',
            'hasFilters'
        ));
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
    public function restore(
        Teacher $teacher,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('restore', $teacher);

        abort_unless($teacher->trashed(), 404);

        DB::transaction(function () use (
            $teacher,
            $auditLogger
        ): void {
            $teacher->restore();

            $auditLogger->record(
                'teacher.restored',
                $teacher,
                [
                    'changed_fields' => ['deleted_at'],
                ]
            );
        });

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
        Teacher $teacher,
        AuditLogger $auditLogger
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

        DB::transaction(function () use (
            $teacher,
            $validated,
            $auditLogger
        ): void {
            $teacher->fill($validated);

            $changedFields = array_keys($teacher->getDirty());

            if ($changedFields === []) {
                return;
            }

            $teacher->save();

            $auditLogger->record(
                'teacher.updated',
                $teacher,
                [
                    'changed_fields' => $changedFields,
                ]
            );
        });

        return to_route('teachers.show', $teacher)
            ->with('success', 'Teacher information updated successfully.');
    }

    /**
     * Archive a teacher using soft deletion.
     */
    public function destroy(
        Teacher $teacher,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $teacher);

        DB::transaction(function () use (
            $teacher,
            $auditLogger
        ): void {
            $teacher->delete();

            $auditLogger->record(
                'teacher.archived',
                $teacher,
                [
                    'changed_fields' => ['deleted_at'],
                ]
            );
        });

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
    public function store(
        Request $request,
        AuditLogger $auditLogger
    ): RedirectResponse {
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

        $teacher = DB::transaction(function () use (
            $validated,
            $auditLogger
        ): Teacher {
            $teacher = Teacher::create($validated);

            $auditLogger->record(
                'teacher.created',
                $teacher,
                [
                    'changed_fields' => array_keys($validated),
                ]
            );

            return $teacher;
        });

        return to_route('teachers.show', $teacher)
            ->with('success', 'Teacher registered successfully.');
    }
}
