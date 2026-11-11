<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the available academic reports.
     */
    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * Display attendance reports with class, subject and date filters.
     */
    public function attendance(Request $request): View
    {
        Gate::authorize('viewAny', AttendanceSession::class);

        $filters = $request->validate([
            'academic_class_id' => [
                'nullable',
                'integer',
                'exists:academic_classes,id',
            ],
            'subject_id' => [
                'nullable',
                'integer',
                'exists:subjects,id',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $sessionsQuery = AttendanceSession::query()
            ->with([
                'teachingAssignment.teacher' =>
                    fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.subject' =>
                    fn ($relation) => $relation->withTrashed(),
            ])
            ->withCount([
                'studentAttendances',

                'studentAttendances as present_count' =>
                    fn (Builder $query) => $query->where(
                        'status',
                        'present'
                    ),

                'studentAttendances as absent_count' =>
                    fn (Builder $query) => $query->where(
                        'status',
                        'absent'
                    ),

                'studentAttendances as late_count' =>
                    fn (Builder $query) => $query->where(
                        'status',
                        'late'
                    ),

                'studentAttendances as excused_count' =>
                    fn (Builder $query) => $query->where(
                        'status',
                        'excused'
                    ),
            ])
            ->whereHas('teachingAssignment', function (Builder $query) use ($filters): void {
                $query
                    ->when(
                        $filters['academic_class_id'] ?? null,
                        fn (Builder $query, $id) =>
                            $query->where('academic_class_id', $id)
                    )
                    ->when(
                        $filters['subject_id'] ?? null,
                        fn (Builder $query, $id) =>
                            $query->where('subject_id', $id)
                    );
            })
            ->when(
                $filters['start_date'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('attendance_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('attendance_date', '<=', $date)
            );

        $totalSessions = (clone $sessionsQuery)->count();

        $sessions = $sessionsQuery
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /*
         * Calculate attendance totals across all matching sessions,
         * not just the currently displayed page.
         */
        $attendanceSummaryQuery = DB::table('student_attendances')
            ->join(
                'attendance_sessions',
                'attendance_sessions.id',
                '=',
                'student_attendances.attendance_session_id'
            )
            ->join(
                'teaching_assignments',
                'teaching_assignments.id',
                '=',
                'attendance_sessions.teaching_assignment_id'
            )
            ->when(
                $filters['academic_class_id'] ?? null,
                fn ($query, $id) =>
                    $query->where('teaching_assignments.academic_class_id', $id)
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn ($query, $id) =>
                    $query->where('teaching_assignments.subject_id', $id)
            )
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate('attendance_sessions.attendance_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate('attendance_sessions.attendance_date', '<=', $date)
            );

        $attendanceSummary = $attendanceSummaryQuery
            ->select(
                'student_attendances.status',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('student_attendances.status')
            ->pluck('total', 'student_attendances.status')
            ->map(fn ($total) => (int) $total);

        $classes = AcademicClass::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $subjects = Subject::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('reports.attendance', compact(
            'sessions',
            'classes',
            'subjects',
            'filters',
            'totalSessions',
            'attendanceSummary'
        ));
    }

    /**
     * Display examination and grade reports with filters.
     */
    public function examinations(Request $request): View
    {
        Gate::authorize('viewAny', Examination::class);

        $filters = $request->validate([
            'academic_class_id' => [
                'nullable',
                'integer',
                'exists:academic_classes,id',
            ],
            'subject_id' => [
                'nullable',
                'integer',
                'exists:subjects,id',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $examinationsQuery = Examination::query()
            ->with([
                'teachingAssignment.teacher' =>
                    fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.subject' =>
                    fn ($relation) => $relation->withTrashed(),
            ])
            ->withCount('results')
            ->whereHas('teachingAssignment', function (Builder $query) use ($filters): void {
                $query
                    ->when(
                        $filters['academic_class_id'] ?? null,
                        fn (Builder $query, $id) =>
                            $query->where('academic_class_id', $id)
                    )
                    ->when(
                        $filters['subject_id'] ?? null,
                        fn (Builder $query, $id) =>
                            $query->where('subject_id', $id)
                    );
            })
            ->when(
                $filters['start_date'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('exam_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('exam_date', '<=', $date)
            );

        $totalExaminations = (clone $examinationsQuery)->count();

        $examinations = $examinationsQuery
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /*
         * Calculate grade totals across every matching examination,
         * rather than only the current page.
         */
        $gradeSummaryQuery = DB::table('examination_results')
            ->join(
                'examinations',
                'examinations.id',
                '=',
                'examination_results.examination_id'
            )
            ->join(
                'teaching_assignments',
                'teaching_assignments.id',
                '=',
                'examinations.teaching_assignment_id'
            )
            ->when(
                $filters['academic_class_id'] ?? null,
                fn ($query, $id) =>
                    $query->where('teaching_assignments.academic_class_id', $id)
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn ($query, $id) =>
                    $query->where('teaching_assignments.subject_id', $id)
            )
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate('examinations.exam_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate('examinations.exam_date', '<=', $date)
            );

        $gradeSummary = $gradeSummaryQuery
            ->select(
                'examination_results.grade',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('examination_results.grade')
            ->orderBy('examination_results.grade')
            ->pluck('total', 'examination_results.grade')
            ->map(fn ($total) => (int) $total);

        $classes = AcademicClass::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $subjects = Subject::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('reports.examinations', compact(
            'examinations',
            'classes',
            'subjects',
            'filters',
            'totalExaminations',
            'gradeSummary'
        ));
    }

    /**
     * Display students available for academic reports.
     */
    public function students(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $search = trim((string) $request->query('search', ''));

        $studentQuery = auth()->user()->isAdmin()
            ? Student::withTrashed()
            : Student::query();

        $students = $studentQuery
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('student_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('student_number')
            ->paginate(10)
            ->withQueryString();

        return view('reports.students.index', compact(
            'students',
            'search'
        ));
    }

    /**
     * Display an individual student's academic report.
     */
    public function student(Student $student): View
    {
        if ($student->trashed()) {
            Gate::authorize('viewArchived', Student::class);
        } else {
            Gate::authorize('view', $student);
        }

        /*
         * Retrieve all enrollment records, including completed
         * and withdrawn enrollments.
         */
        $enrollments = $student->enrollments()
            ->with([
                'academicClass' => function ($relation): void {
                    $relation->withTrashed();
                },
            ])
            ->orderByDesc('enrollment_date')
            ->get();

        $enrollmentIds = $enrollments->modelKeys();

        /*
         * Enrollment statistics.
         */
        $enrollmentStatusCounts = $enrollments
            ->groupBy('status')
            ->map(fn ($records) => $records->count());

        $enrollmentStats = [
            'total' => $enrollments->count(),
            'active' => (int) $enrollmentStatusCounts->get(
                StudentEnrollment::STATUS_ACTIVE,
                0
            ),
            'completed' => (int) $enrollmentStatusCounts->get(
                StudentEnrollment::STATUS_COMPLETED,
                0
            ),
            'withdrawn' => (int) $enrollmentStatusCounts->get(
                StudentEnrollment::STATUS_WITHDRAWN,
                0
            ),
        ];

        /*
         * Attendance statistics and records.
         */
        $attendanceQuery = StudentAttendance::query()
            ->whereIn('student_enrollment_id', $enrollmentIds);

        $attendanceSummary = (clone $attendanceQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        $attendanceRecords = (clone $attendanceQuery)
            ->with([
                'studentEnrollment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'attendanceSession.teachingAssignment.teacher' =>
                    fn ($relation) => $relation->withTrashed(),

                'attendanceSession.teachingAssignment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'attendanceSession.teachingAssignment.subject' =>
                    fn ($relation) => $relation->withTrashed(),
            ])
            ->latest('id')
            ->paginate(10, ['*'], 'attendance_page');

        /*
         * Examination result statistics and records.
         */
        $examinationResultQuery = ExaminationResult::query()
            ->whereIn('student_enrollment_id', $enrollmentIds);

        $gradeSummary = (clone $examinationResultQuery)
            ->select('grade', DB::raw('COUNT(*) as total'))
            ->groupBy('grade')
            ->pluck('total', 'grade')
            ->map(fn ($total) => (int) $total);

        $examinationResults = (clone $examinationResultQuery)
            ->with([
                'studentEnrollment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'examination.teachingAssignment.teacher' =>
                    fn ($relation) => $relation->withTrashed(),

                'examination.teachingAssignment.academicClass' =>
                    fn ($relation) => $relation->withTrashed(),

                'examination.teachingAssignment.subject' =>
                    fn ($relation) => $relation->withTrashed(),
            ])
            ->latest('id')
            ->paginate(10, ['*'], 'results_page');

        return view('reports.students.show', compact(
            'student',
            'enrollments',
            'enrollmentStats',
            'attendanceSummary',
            'attendanceRecords',
            'gradeSummary',
            'examinationResults'
        ));
    }
}