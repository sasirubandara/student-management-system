<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display students with optional search, status, and date filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    Student::STATUS_ACTIVE,
                    Student::STATUS_INACTIVE,
                ]),
            ],
            'enrolled_from' => ['nullable', 'date_format:Y-m-d'],
            'enrolled_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:enrolled_from',
            ],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';
        $enrolledFrom = $validated['enrolled_from'] ?? '';
        $enrolledTo = $validated['enrolled_to'] ?? '';

        $students = Student::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('student_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($enrolledFrom !== '', function (Builder $query) use ($enrolledFrom): void {
                $query->whereDate('enrollment_date', '>=', $enrolledFrom);
            })
            ->when($enrolledTo !== '', function (Builder $query) use ($enrolledTo): void {
                $query->whereDate('enrollment_date', '<=', $enrolledTo);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $hasFilters = $search !== ''
            || $status !== ''
            || $enrolledFrom !== ''
            || $enrolledTo !== '';

        return view('students.index', compact(
            'students',
            'search',
            'status',
            'enrolledFrom',
            'enrolledTo',
            'hasFilters'
        ));
    }

    /**
     * Display archived students.
     */
    public function archived(): View
    {
        Gate::authorize('viewArchived', Student::class);

        $students = Student::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(10);

        return view('students.archived', compact('students'));
    }

    /**
     * Restore an archived student.
     */
    public function restore(
        Student $student,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('restore', $student);

        abort_unless($student->trashed(), 404);

        DB::transaction(function () use (
            $student,
            $auditLogger
        ): void {
            $student->restore();

            $auditLogger->record(
                'student.restored',
                $student,
                [
                    'changed_fields' => ['deleted_at'],
                ]
            );
        });

        return to_route('students.show', $student)
            ->with('success', 'Student restored successfully.');
    }

    /**
     * Display an individual student's profile.
     */
    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        return view('students.show', compact('student'));
    }

    /**
     * Display the student editing form.
     */
    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        return view('students.edit', compact('student'));
    }

    /**
     * Update an existing student's information.
     */
    public function update(
        Request $request,
        Student $student,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $student);

        $validated = $request->validate([
            'student_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'student_number')
                    ->ignore($student->id),
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        DB::transaction(function () use (
            $student,
            $validated,
            $auditLogger
        ): void {
            $student->fill($validated);

            // Store field names only, not personal information.
            $changedFields = array_keys($student->getDirty());

            if ($changedFields === []) {
                return;
            }

            $student->save();

            $auditLogger->record(
                'student.updated',
                $student,
                [
                    'changed_fields' => $changedFields,
                ]
            );
        });

        return to_route('students.show', $student)
            ->with('success', 'Student information updated successfully.');
    }

    /**
     * Archive a student using soft deletion.
     */
    public function destroy(
        Student $student,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $student);

        DB::transaction(function () use (
            $student,
            $auditLogger
        ): void {
            $student->delete();

            $auditLogger->record(
                'student.archived',
                $student,
                [
                    'changed_fields' => ['deleted_at'],
                ]
            );
        });

        return to_route('students.index')
            ->with('success', 'Student archived successfully.');
    }

    /**
     * Display the student registration form.
     */
    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('students.create');
    }

    /**
     * Store a newly registered student.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        NotificationService $notificationService
    ): RedirectResponse {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'student_number' => [
                'required',
                'string',
                'max:20',
                'unique:students,student_number',
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'enrollment_date' => ['required', 'date'],
        ]);

        $actor = $request->user();

        DB::transaction(function () use (
            $validated,
            $auditLogger,
            $notificationService,
            $actor
        ): void {
            $student = Student::create($validated);

            // Record the student creation in the audit log.
            $auditLogger->record(
                'student.created',
                $student,
                [
                    'changed_fields' => array_keys($validated),
                ]
            );

            // Notify other active administrators and staff.
            $notificationService->notifyOtherActiveUsers(
                actor: $actor,
                event: 'student.created',
                title: 'New student registered',
                message: 'A new student record has been created.',
                url: route('students.show', $student),
                icon: 'user-plus',
            );
        });

        return to_route('students.create')
            ->with('success', 'Student registered successfully.');
    }
}
