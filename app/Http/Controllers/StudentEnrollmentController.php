<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentEnrollmentController extends Controller
{
    /**
     * Display enrollments with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StudentEnrollment::class);

        $search = trim((string) $request->query('search', ''));

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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('student-enrollments.index', compact(
            'enrollments',
            'search'
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
    public function store(Request $request): RedirectResponse
    {
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

        $enrollment = StudentEnrollment::create($validated);

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
        StudentEnrollment $studentEnrollment
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

        $studentEnrollment->update($validated);

        return to_route('student-enrollments.show', $studentEnrollment)
            ->with('success', 'Student enrollment updated successfully.');
    }
}