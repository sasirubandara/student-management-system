<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Examination;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\AuditLogger;
use App\Services\GradeCalculator;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
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

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'exam_type' => [
                'nullable',
                Rule::in(Examination::EXAM_TYPES),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'academic_class_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_classes', 'id'),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'exam_from' => ['nullable', 'date_format:Y-m-d'],
            'exam_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:exam_from',
            ],
        ]);

        $search = trim($validated['search'] ?? '');
        $examType = $validated['exam_type'] ?? '';
        $teacherId = $validated['teacher_id'] ?? '';
        $academicClassId = $validated['academic_class_id'] ?? '';
        $subjectId = $validated['subject_id'] ?? '';
        $examFrom = $validated['exam_from'] ?? '';
        $examTo = $validated['exam_to'] ?? '';

        $examinations = Examination::query()
            ->with([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
                'creator',
            ])
            ->withCount('results')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('exam_type', 'like', $term)
                        ->orWhereHas(
                            'teachingAssignment.teacher',
                            function (Builder $teacherQuery) use ($term): void {
                                $teacherQuery
                                    ->where('teacher_number', 'like', $term)
                                    ->orWhere('first_name', 'like', $term)
                                    ->orWhere('last_name', 'like', $term);
                            }
                        )
                        ->orWhereHas(
                            'teachingAssignment.academicClass',
                            function (Builder $classQuery) use ($term): void {
                                $classQuery
                                    ->where('name', 'like', $term)
                                    ->orWhere('code', 'like', $term);
                            }
                        )
                        ->orWhereHas(
                            'teachingAssignment.subject',
                            function (Builder $subjectQuery) use ($term): void {
                                $subjectQuery
                                    ->where('name', 'like', $term)
                                    ->orWhere('code', 'like', $term);
                            }
                        );
                });
            })
            ->when($examType !== '', function (Builder $query) use ($examType): void {
                $query->where('exam_type', $examType);
            })
            ->when(
                $teacherId !== '' || $academicClassId !== '' || $subjectId !== '',
                function (Builder $query) use (
                    $teacherId,
                    $academicClassId,
                    $subjectId
                ): void {
                    $query->whereHas(
                        'teachingAssignment',
                        function (Builder $assignmentQuery) use (
                            $teacherId,
                            $academicClassId,
                            $subjectId
                        ): void {
                            if ($teacherId !== '') {
                                $assignmentQuery->where('teacher_id', $teacherId);
                            }

                            if ($academicClassId !== '') {
                                $assignmentQuery->where(
                                    'academic_class_id',
                                    $academicClassId
                                );
                            }

                            if ($subjectId !== '') {
                                $assignmentQuery->where('subject_id', $subjectId);
                            }
                        }
                    );
                }
            )
            ->when($examFrom !== '', function (Builder $query) use ($examFrom): void {
                $query->whereDate('exam_date', '>=', $examFrom);
            })
            ->when($examTo !== '', function (Builder $query) use ($examTo): void {
                $query->whereDate('exam_date', '<=', $examTo);
            })
            ->latest('exam_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $teachers = Teacher::withTrashed()
            ->whereHas('teachingAssignments.examinations')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $academicClasses = AcademicClass::withTrashed()
            ->whereHas('teachingAssignments.examinations')
            ->orderBy('name')
            ->get();

        $subjects = Subject::withTrashed()
            ->whereHas('teachingAssignments.examinations')
            ->orderBy('name')
            ->get();

        $examTypes = Examination::EXAM_TYPES;

        $hasFilters = $search !== ''
            || $examType !== ''
            || $teacherId !== ''
            || $academicClassId !== ''
            || $subjectId !== ''
            || $examFrom !== ''
            || $examTo !== '';

        return view('examinations.index', compact(
            'examinations',
            'teachers',
            'academicClasses',
            'subjects',
            'examTypes',
            'search',
            'examType',
            'teacherId',
            'academicClassId',
            'subjectId',
            'examFrom',
            'examTo',
            'hasFilters'
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
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        NotificationService $notificationService
    ): RedirectResponse {
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

        $actor = $request->user();

        $examination = DB::transaction(function () use (
            $validated,
            $actor,
            $auditLogger,
            $notificationService
        ): Examination {
            $examination = Examination::create([
                ...$validated,
                'created_by' => $actor->id,
            ]);

            // Record examination creation in the audit log.
            $auditLogger->record(
                'examination.created',
                $examination,
                [
                    'changed_fields' => [
                        ...array_keys($validated),
                        'created_by',
                    ],
                ]
            );

            // Load related records for the notification message.
            $examination->load([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ]);

            $assignment = $examination->teachingAssignment;

            $examType = ucwords(
                str_replace('_', ' ', $examination->exam_type)
            );

            $examDate = $examination->exam_date->format('M d, Y');

            // Notify other active users.
            $notificationService->notifyOtherActiveUsers(
                actor: $actor,
                event: 'examination.created',
                title: 'New examination scheduled',
                message: $examination->title
                    .' ('.$examType.') for '
                    .$assignment->academicClass->name
                    .' — '
                    .$assignment->subject->name
                    .' is scheduled for '
                    .$examDate
                    .'.',
                url: route('examinations.show', $examination),
                icon: 'clipboard-check',
            );

            return $examination;
        });

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
        Examination $examination,
        AuditLogger $auditLogger
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
                'teaching_assignment_id' => 'The teaching assignment cannot be changed after examination results have been recorded.',
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
                'maximum_marks' => 'Maximum marks cannot be lower than an already recorded result.',
            ]);
        }

        DB::transaction(function () use (
            $examination,
            $validated,
            $auditLogger
        ): void {
            $examination->fill($validated);

            $changedFields = array_keys($examination->getDirty());

            // There is nothing to audit when the examination is unchanged.
            if ($changedFields === []) {
                return;
            }

            $previousMaximumMarks = $examination->getOriginal('maximum_marks');

            $maximumMarksChanged = in_array(
                'maximum_marks',
                $changedFields,
                true
            );

            $examination->save();

            $gradesRecalculated = 0;

            /*
             * Recalculate existing grades if maximum marks change.
             */
            if ($maximumMarksChanged) {
                $calculator = app(GradeCalculator::class);

                $results = $examination->results()->get();

                $gradesRecalculated = $results->count();

                $results->each(function ($result) use (
                    $calculator,
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

            $properties = [
                'changed_fields' => $changedFields,
            ];

            if ($maximumMarksChanged) {
                $properties['changes'] = [
                    'maximum_marks' => [
                        'old' => $previousMaximumMarks,
                        'new' => $validated['maximum_marks'],
                    ],
                ];

                $properties['grades_recalculated'] = $gradesRecalculated;
            }

            $auditLogger->record(
                'examination.updated',
                $examination,
                $properties
            );
        });

        return redirect()
            ->route('examinations.show', $examination)
            ->with('success', 'Examination updated successfully.');
    }

    /**
     * Delete an examination that has no results.
     */
    public function destroy(
        Examination $examination,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $examination);

        $deleted = DB::transaction(function () use (
            $examination,
            $auditLogger
        ): bool {
            $lockedExamination = Examination::query()
                ->whereKey($examination->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExamination->results()->exists()) {
                return false;
            }

            $auditProperties = [
                'record' => [
                    'title' => $lockedExamination->title,
                    'exam_type' => $lockedExamination->exam_type,
                    'exam_date' => (string) $lockedExamination->exam_date,
                ],
                'changed_fields' => ['deleted'],
            ];

            $lockedExamination->delete();

            $auditLogger->record(
                'examination.deleted',
                $lockedExamination,
                $auditProperties
            );

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('examinations.show', $examination)
                ->with(
                    'error',
                    'This examination cannot be removed because it contains examination results.'
                );
        }

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
                'teaching_assignment_id' => 'The selected teaching assignment is inactive or unavailable.',
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
                'pass_marks' => 'Pass marks cannot exceed maximum marks.',
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
                'title' => 'An examination with this title already exists for this teaching assignment and date.',
            ]);
        }
    }
}
