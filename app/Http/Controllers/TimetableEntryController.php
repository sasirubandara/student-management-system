<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimetableEntryRequest;
use App\Http\Requests\UpdateTimetableEntryRequest;
use App\Models\AcademicClass;
use App\Models\AcademicTerm;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Services\Timetables\TimetableSchedulingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimetableEntryController extends Controller
{
    /**
     * Display timetable entries with optional filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TimetableEntry::class);

        $validated = $request->validate([
            'academic_term_id' => [
                'nullable',
                'integer',
                'exists:academic_terms,id',
            ],
            'academic_class_id' => [
                'nullable',
                'integer',
                'exists:academic_classes,id',
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                'exists:teachers,id',
            ],
        ]);

        $termId = $validated['academic_term_id'] ?? null;
        $classId = $validated['academic_class_id'] ?? null;
        $teacherId = $validated['teacher_id'] ?? null;

        $entries = TimetableEntry::query()
            ->with([
                'academicTerm.academicYear',
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ])
            ->when(
                $termId !== null,
                fn (Builder $query) => $query->where(
                    'academic_term_id',
                    $termId
                )
            )
            ->when(
                $classId !== null,
                fn (Builder $query) => $query->whereHas(
                    'teachingAssignment',
                    fn (Builder $assignmentQuery) => $assignmentQuery->where(
                        'academic_class_id',
                        $classId
                    )
                )
            )
            ->when(
                $teacherId !== null,
                fn (Builder $query) => $query->whereHas(
                    'teachingAssignment',
                    fn (Builder $assignmentQuery) => $assignmentQuery->where(
                        'teacher_id',
                        $teacherId
                    )
                )
            )
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $terms = AcademicTerm::query()
            ->with('academicYear')
            ->orderBy('start_date', 'desc')
            ->get();

        $academicClasses = AcademicClass::query()
            ->orderBy('name')
            ->get();

        $teachers = Teacher::query()
            ->orderBy('teacher_number')
            ->get();

        return view('timetables.index', compact(
            'entries',
            'terms',
            'academicClasses',
            'teachers',
            'termId',
            'classId',
            'teacherId'
        ));
    }

    /**
     * Display the weekly timetable for an academic class.
     */
    public function classTimetable(
        Request $request,
        AcademicClass $academicClass
    ): View {
        Gate::authorize('viewAny', TimetableEntry::class);

        $validated = $request->validate([
            'academic_term_id' => [
                'nullable',
                'integer',
                'exists:academic_terms,id',
            ],
        ]);

        $termId = $validated['academic_term_id'] ?? null;

        $terms = AcademicTerm::query()
            ->whereHas('academicYear', function (Builder $query) use (
                $academicClass
            ): void {
                $query->where('year', $academicClass->academic_year);
            })
            ->with('academicYear')
            ->orderBy('start_date', 'desc')
            ->get();

        if (
            $termId !== null
            && ! $terms->contains('id', $termId)
        ) {
            throw ValidationException::withMessages([
                'academic_term_id' => 'The selected academic term does not belong to this academic class year.',
            ]);
        }

        $entries = TimetableEntry::query()
            ->with([
                'academicTerm.academicYear',
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ])
            ->whereHas(
                'teachingAssignment',
                fn (Builder $query) => $query->where(
                    'academic_class_id',
                    $academicClass->id
                )
            )
            ->when(
                $termId !== null,
                fn (Builder $query) => $query->where(
                    'academic_term_id',
                    $termId
                ),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return view('timetables.weekly', [
            'title' => 'Class Weekly Timetable',
            'description' => $academicClass->name,
            'filterRoute' => route(
                'timetables.classes.show',
                $academicClass
            ),
            'terms' => $terms,
            'termId' => $termId,
            'entriesByDay' => $entries->groupBy('day_of_week'),
            'showClass' => false,
            'showTeacher' => true,
        ]);
    }

    /**
     * Display the weekly timetable for a teacher.
     */
    public function teacherTimetable(
        Request $request,
        Teacher $teacher
    ): View {
        Gate::authorize('viewAny', TimetableEntry::class);

        $validated = $request->validate([
            'academic_term_id' => [
                'nullable',
                'integer',
                'exists:academic_terms,id',
            ],
        ]);

        $termId = $validated['academic_term_id'] ?? null;

        $terms = AcademicTerm::query()
            ->with('academicYear')
            ->orderBy('start_date', 'desc')
            ->get();

        $entries = TimetableEntry::query()
            ->with([
                'academicTerm.academicYear',
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ])
            ->whereHas(
                'teachingAssignment',
                fn (Builder $query) => $query->where(
                    'teacher_id',
                    $teacher->id
                )
            )
            ->when(
                $termId !== null,
                fn (Builder $query) => $query->where(
                    'academic_term_id',
                    $termId
                ),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return view('timetables.weekly', [
            'title' => 'Teacher Weekly Timetable',
            'description' => trim(
                $teacher->first_name.' '.$teacher->last_name
            ),
            'filterRoute' => route(
                'timetables.teachers.show',
                $teacher
            ),
            'terms' => $terms,
            'termId' => $termId,
            'entriesByDay' => $entries->groupBy('day_of_week'),
            'showClass' => true,
            'showTeacher' => false,
        ]);
    }

    /**
     * Display the timetable creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', TimetableEntry::class);

        $terms = AcademicTerm::query()
            ->with('academicYear')
            ->orderBy('start_date', 'desc')
            ->orderBy('term_number')
            ->get();

        $assignments = TeachingAssignment::query()
            ->whereHas('teacher', function (Builder $query): void {
                $query->where('status', Teacher::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->whereHas('academicClass', function (Builder $query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->whereHas('subject', function (Builder $query): void {
                $query->where('status', Subject::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->with([
                'teacher',
                'academicClass',
                'subject',
            ])
            ->orderBy('id')
            ->get();

        return view('timetables.create', compact(
            'terms',
            'assignments'
        ));
    }

    /**
     * Store a new recurring weekly timetable entry.
     */
    public function store(
        StoreTimetableEntryRequest $request,
        TimetableSchedulingService $service
    ): RedirectResponse {
        $entry = $service->create(
            $request->validated(),
            $request->user()
        );

        return to_route('timetables.index')
            ->with(
                'success',
                'Timetable entry created successfully.'
            );
    }

    /**
     * Display an individual timetable entry.
     */
    public function show(TimetableEntry $timetableEntry): View
    {
        Gate::authorize('view', $timetableEntry);

        $timetableEntry->load([
            'academicTerm.academicYear',
            'teachingAssignment.teacher',
            'teachingAssignment.academicClass',
            'teachingAssignment.subject',
            'creator',
        ]);

        return view('timetables.show', compact('timetableEntry'));
    }

    /**
     * Display the timetable editing form.
     */
    public function edit(TimetableEntry $timetableEntry): View
    {
        Gate::authorize('update', $timetableEntry);

        $terms = AcademicTerm::query()
            ->with('academicYear')
            ->orderBy('start_date', 'desc')
            ->orderBy('term_number')
            ->get();

        // Include all existing assignments so administrators
        // can see the currently selected historical assignment.
        // The scheduling service validates the selected assignment
        // before accepting changes.
        $assignments = TeachingAssignment::query()
            ->with([
                'teacher',
                'academicClass',
                'subject',
            ])
            ->orderBy('id')
            ->get();

        return view('timetables.edit', compact(
            'timetableEntry',
            'terms',
            'assignments'
        ));
    }

    /**
     * Update an existing timetable entry.
     */
    public function update(
        UpdateTimetableEntryRequest $request,
        TimetableEntry $timetableEntry,
        TimetableSchedulingService $service
    ): RedirectResponse {
        $updatedEntry = $service->update(
            $timetableEntry,
            $request->validated(),
            $request->user()
        );

        return to_route('timetables.show', $updatedEntry)
            ->with('success', 'Timetable entry updated successfully.');
    }

    /**
     * Delete an existing timetable entry.
     */
    public function destroy(
        TimetableEntry $timetableEntry,
        TimetableSchedulingService $service
    ): RedirectResponse {
        Gate::authorize('delete', $timetableEntry);

        $service->delete(
            $timetableEntry,
            request()->user()
        );

        return to_route('timetables.index')
            ->with('success', 'Timetable entry deleted successfully.');
    }
}
