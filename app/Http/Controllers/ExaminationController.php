<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Examination;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\GradeCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExaminationController extends Controller
{
    /**
     * Display the examination directory.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Examination::class);

        $search = trim((string) $request->query('search', ''));

        $examinations = Examination::query()
            ->with([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
                'creator',
            ])
            ->withCount('results')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('exam_type', 'like', "%{$search}%")
                        ->orWhereHas(
                            'teachingAssignment.teacher',
                            function ($teacherQuery) use ($search): void {
                                $teacherQuery
                                    ->where('teacher_number', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            }
                        )
                        ->orWhereHas(
                            'teachingAssignment.academicClass',
                            function ($classQuery) use ($search): void {
                                $classQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            }
                        )
                        ->orWhereHas(
                            'teachingAssignment.subject',
                            function ($subjectQuery) use ($search): void {
                                $subjectQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            }
                        );
                });
            })
            ->latest('exam_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('examinations.index', compact(
            'examinations',
            'search'
        ));
    }

    /**
     * Display the examination creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', Examination::class);

        $teachingAssignments = $this->activeTeachingAssignments();

        return view('examinations.create', compact(
            'teachingAssignments'
        ));
    }

    /**
     * Store a newly created examination.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Examination::class);

        $validated = $request->validate([
            'teaching_assignment_id' => [
                'required',
                'integer',
                'exists:teaching_assignments,id',
            ],
            'title' => [
                'required',
                'string',
                'max:150',
            ],
            'exam_type' => [
                'required',
                Rule::in(Examination::EXAM_TYPES),
            ],
            'exam_date' => [
                'required',
                'date',
            ],
            'maximum_marks' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
            ],
            'pass_marks' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->validatePassMarks($validated);

        $this->findActiveTeachingAssignment(
            (int) $validated['teaching_assignment_id']
        );

        $this->ensureNoDuplicateExamination($validated);

        $examination = Examination::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination created successfully.');
    }

    /**
     * Display an examination and its results.
     */
    public function show(Examination $examination): View
    {
        Gate::authorize('view', $examination);

        $examination->load([
            'teachingAssignment.teacher',
            'teachingAssignment.academicClass',
            'teachingAssignment.subject',
            'creator',
            'results.studentEnrollment.student',
        ]);

        $eligibleEnrollments = StudentEnrollment::query()
            ->where(
                'academic_class_id',
                $examination->teachingAssignment->academic_class_id
            )
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', function ($query): void {
                $query->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->with('student')
            ->orderBy('student_id')
            ->get();

        return view('examinations.show', compact(
            'examination',
            'eligibleEnrollments'
        ));
    }

    /**
     * Display the examination editing form.
     */
    public function edit(Examination $examination): View
    {
        Gate::authorize('update', $examination);

        $examination->loadCount('results');

        $teachingAssignments = $this->activeTeachingAssignments();

        return view('examinations.edit', compact(
            'examination',
            'teachingAssignments'
        ));
    }

    /**
     * Update an examination.
     */
    public function update(
        Request $request,
        Examination $examination
    ): RedirectResponse {
        Gate::authorize('update', $examination);

        $validated = $request->validate([
            'teaching_assignment_id' => [
                'required',
                'integer',
                'exists:teaching_assignments,id',
            ],
            'title' => [
                'required',
                'string',
                'max:150',
            ],
            'exam_type' => [
                'required',
                Rule::in(Examination::EXAM_TYPES),
            ],
            'exam_date' => [
                'required',
                'date',
            ],
            'maximum_marks' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
            ],
            'pass_marks' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->validatePassMarks($validated);

        $this->findActiveTeachingAssignment(
            (int) $validated['teaching_assignment_id']
        );

        $this->ensureNoDuplicateExamination(
            $validated,
            $examination
        );

        $hasResults = $examination->results()->exists();

        if (
            $hasResults
            && (int) $validated['teaching_assignment_id']
                !== (int) $examination->teaching_assignment_id
        ) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' =>
                    'The teaching assignment cannot be changed after examination results have been recorded.',
            ]);
        }

        $highestRecordedMark = $examination->results()
            ->max('marks_obtained');

        if (
            $highestRecordedMark !== null
            && (float) $validated['maximum_marks']
                < (float) $highestRecordedMark
        ) {
            throw ValidationException::withMessages([
                'maximum_marks' =>
                    'Maximum marks cannot be lower than an already recorded result.',
            ]);
        }

        $maximumMarksChanged = (float) $examination->maximum_marks
            !== (float) $validated['maximum_marks'];

        DB::transaction(function () use (
            $examination,
            $validated,
            $maximumMarksChanged
        ): void {
            $examination->update($validated);

            /*
             * Recalculate existing grades if maximum marks change.
             */
            if ($maximumMarksChanged) {
                $calculator = app(GradeCalculator::class);

                $examination->results()
                    ->get()
                    ->each(function ($result) use (
                        $calculator,
                        $examination,
                        $validated
                    ): void {
                        $result->update([
                            'grade' => $calculator->calculate(
                                $result->marks_obtained,
                                $validated['maximum_marks']
                            ),
                        ]);
                    });
            }
        });

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination updated successfully.');
    }

    /**
     * Delete an examination that has no results.
     */
    public function destroy(Examination $examination): RedirectResponse
    {
        Gate::authorize('delete', $examination);

        if ($examination->results()->exists()) {
            return redirect()
                ->route('examinations.show', $examination)
                ->with(
                    'error',
                    'This examination cannot be removed because it contains examination results.'
                );
        }

        $examination->delete();

        return redirect()
            ->route('examinations.index')
            ->with('success', 'Examination removed successfully.');
    }

    /**
     * Retrieve active teaching assignments.
     */
    private function activeTeachingAssignments(): Collection
    {
        return TeachingAssignment::query()
            ->whereHas('teacher', function ($query): void {
                $query->where('status', Teacher::STATUS_ACTIVE);
            })
            ->whereHas('academicClass', function ($query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE);
            })
            ->whereHas('subject', function ($query): void {
                $query->where('status', Subject::STATUS_ACTIVE);
            })
            ->with([
                'teacher',
                'academicClass',
                'subject',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Find an active teaching assignment or reject the request.
     */
    private function findActiveTeachingAssignment(
        int $teachingAssignmentId
    ): TeachingAssignment {
        $assignment = TeachingAssignment::query()
            ->whereKey($teachingAssignmentId)
            ->whereHas('teacher', function ($query): void {
                $query->where('status', Teacher::STATUS_ACTIVE);
            })
            ->whereHas('academicClass', function ($query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE);
            })
            ->whereHas('subject', function ($query): void {
                $query->where('status', Subject::STATUS_ACTIVE);
            })
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' =>
                    'The selected teaching assignment is inactive or unavailable.',
            ]);
        }

        return $assignment;
    }

    /**
     * Ensure pass marks do not exceed maximum marks.
     */
    private function validatePassMarks(array $validated): void
    {
        $passMarks = $validated['pass_marks'] ?? null;

        if (
            $passMarks !== null
            && (float) $passMarks > (float) $validated['maximum_marks']
        ) {
            throw ValidationException::withMessages([
                'pass_marks' =>
                    'Pass marks cannot exceed maximum marks.',
            ]);
        }
    }

    /**
     * Prevent duplicate examinations for an assignment, title, and date.
     */
    private function ensureNoDuplicateExamination(
        array $validated,
        ?Examination $ignore = null
    ): void {
        $query = Examination::query()
            ->where(
                'teaching_assignment_id',
                $validated['teaching_assignment_id']
            )
            ->where('title', $validated['title'])
            ->whereDate('exam_date', $validated['exam_date']);

        if ($ignore) {
            $query->where('id', '!=', $ignore->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'title' =>
                    'An examination with this title already exists for this teaching assignment and date.',
            ]);
        }
    }
}