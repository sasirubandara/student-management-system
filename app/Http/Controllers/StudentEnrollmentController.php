<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentEnrollmentController extends Controller
{
    /**
     * Display enrollments with optional search and advanced filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StudentEnrollment::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'academic_class_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_classes', 'id'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    StudentEnrollment::STATUS_ACTIVE,
                    StudentEnrollment::STATUS_COMPLETED,
                    StudentEnrollment::STATUS_WITHDRAWN,
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
        $academicClassId = $validated['academic_class_id'] ?? '';
        $status = $validated['status'] ?? '';
        $enrolledFrom = $validated['enrolled_from'] ?? '';
        $enrolledTo = $validated['enrolled_to'] ?? '';

        $enrollments = StudentEnrollment::query()
            ->with(['student', 'academicClass'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->whereHas('student', function (Builder $query) use ($term): void {
                        $query->where('student_number', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    })
                        ->orWhereHas('academicClass', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', $term)
                                ->orWhere('code', 'like', $term);
                        })
                        ->orWhere('status', 'like', $term);
                });
            })
            ->when($academicClassId !== '', function (Builder $query) use ($academicClassId): void {
                $query->where('academic_class_id', $academicClassId);
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

        $academicClasses = AcademicClass::withTrashed()
            ->whereHas('enrollments')
            ->orderBy('name')
            ->orderBy('academic_year')
            ->get();

        $hasFilters = $search !== ''
            || $academicClassId !== ''
            || $status !== ''
            || $enrolledFrom !== ''
            || $enrolledTo !== '';

        return view('student-enrollments.index', compact(
            'enrollments',
            'academicClasses',
            'search',
            'academicClassId',
            'status',
            'enrolledFrom',
            'enrolledTo',
            'hasFilters'
        ));
    }

    /**
     * Display the enrollment creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', StudentEnrollment::class);

        $students = Student::query()
            ->where('status', 'active')
            ->orderBy('student_number')
            ->get();

        $academicClasses = AcademicClass::query()
            ->where('status', AcademicClass::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('student-enrollments.create', compact(
            'students',
            'academicClasses'
        ));
    }

    /**
     * Store a new student enrollment.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        NotificationService $notificationService
    ): RedirectResponse {
        Gate::authorize('create', StudentEnrollment::class);

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),

                Rule::unique('student_enrollments', 'student_id')
                    ->where('academic_class_id', $request->input('academic_class_id')),
            ],

            'academic_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('status', AcademicClass::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],

            'enrollment_date' => ['required', 'date'],

            'status' => [
                'required',
                Rule::in([
                    StudentEnrollment::STATUS_ACTIVE,
                    StudentEnrollment::STATUS_COMPLETED,
                    StudentEnrollment::STATUS_WITHDRAWN,
                ]),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $actor = $request->user();

        $enrollment = DB::transaction(function () use (
            $validated,
            $auditLogger,
            $notificationService,
            $actor
        ): StudentEnrollment {
            $enrollment = StudentEnrollment::create($validated);

            // Record enrollment creation in the audit log.
            $auditLogger->record(
                'enrollment.created',
                $enrollment,
                [
                    'changed_fields' => [
                        'student_id',
                        'academic_class_id',
                        'enrollment_date',
                        'status',
                    ],
                ]
            );

            // Load related records for the notification message.
            $enrollment->load(['student', 'academicClass']);

            // Notify other active users.
            $notificationService->notifyOtherActiveUsers(
                actor: $actor,
                event: 'enrollment.created',
                title: 'New student enrollment',
                message: $enrollment->student->first_name
                    .' '
                    .$enrollment->student->last_name
                    .' was enrolled in '
                    .$enrollment->academicClass->name
                    .'.',
                url: route('student-enrollments.show', $enrollment),
                icon: 'graduation-cap',
            );

            return $enrollment;
        });

        return to_route('student-enrollments.show', $enrollment)
            ->with('success', 'Student enrollment created successfully.');
    }

    /**
     * Display an individual enrollment.
     */
    public function show(StudentEnrollment $studentEnrollment): View
    {
        Gate::authorize('view', $studentEnrollment);

        $studentEnrollment->load(['student', 'academicClass']);

        return view('student-enrollments.show', compact(
            'studentEnrollment'
        ));
    }

    /**
     * Display the enrollment editing form.
     */
    public function edit(StudentEnrollment $studentEnrollment): View
    {
        Gate::authorize('update', $studentEnrollment);

        $studentEnrollment->load(['student', 'academicClass']);

        return view('student-enrollments.edit', compact(
            'studentEnrollment'
        ));
    }

    /**
     * Update an existing enrollment.
     */
    public function update(
        Request $request,
        StudentEnrollment $studentEnrollment,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $studentEnrollment);

        $validated = $request->validate([
            'enrollment_date' => ['required', 'date'],

            'status' => [
                'required',
                Rule::in([
                    StudentEnrollment::STATUS_ACTIVE,
                    StudentEnrollment::STATUS_COMPLETED,
                    StudentEnrollment::STATUS_WITHDRAWN,
                ]),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use (
            $studentEnrollment,
            $validated,
            $auditLogger
        ): void {
            $studentEnrollment->fill($validated);

            $changedFields = array_keys($studentEnrollment->getDirty());

            if ($changedFields === []) {
                return;
            }

            $studentEnrollment->save();

            $auditLogger->record(
                'enrollment.updated',
                $studentEnrollment,
                [
                    'changed_fields' => $changedFields,
                ]
            );
        });

        return to_route('student-enrollments.show', $studentEnrollment)
            ->with('success', 'Student enrollment updated successfully.');
    }
}
