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
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard and statistics.
     */
    public function index(): View
    {
        $isAdmin = auth()->user()->isAdmin();

        /*
         * Student statistics.
         * Soft-deleted students are excluded from ordinary counts.
         */
        $studentStats = [
            'total' => Student::count(),

            'active' => Student::where(
                'status',
                Student::STATUS_ACTIVE
            )->count(),

            'inactive' => Student::where(
                'status',
                Student::STATUS_INACTIVE
            )->count(),

            'archived' => $isAdmin
                ? Student::onlyTrashed()->count()
                : null,
        ];

        /*
         * Teacher statistics.
         */
        $teacherStats = [
            'total' => Teacher::count(),

            'active' => Teacher::where(
                'status',
                Teacher::STATUS_ACTIVE
            )->count(),

            'inactive' => Teacher::where(
                'status',
                Teacher::STATUS_INACTIVE
            )->count(),

            'archived' => $isAdmin
                ? Teacher::onlyTrashed()->count()
                : null,
        ];

        /*
         * Academic class statistics.
         */
        $classStats = [
            'total' => AcademicClass::count(),

            'active' => AcademicClass::where(
                'status',
                AcademicClass::STATUS_ACTIVE
            )->count(),

            'inactive' => AcademicClass::where(
                'status',
                AcademicClass::STATUS_INACTIVE
            )->count(),
        ];

        /*
         * Subject statistics.
         */
        $subjectStats = [
            'total' => Subject::count(),

            'active' => Subject::where(
                'status',
                Subject::STATUS_ACTIVE
            )->count(),

            'inactive' => Subject::where(
                'status',
                Subject::STATUS_INACTIVE
            )->count(),
        ];

        /*
         * Teaching assignment statistics.
         */
        $totalTeachingAssignments = DB::table('teaching_assignments')
            ->count();

        /*
         * Enrollment statistics grouped by status.
         */
        $enrollmentStatusCounts = StudentEnrollment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $enrollmentStats = [
            'total' => StudentEnrollment::count(),

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
         * Attendance statistics.
         */
        $attendanceStatusCounts = StudentAttendance::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendanceStart = now()->startOfMonth()->toDateString();
        $attendanceEnd = now()->endOfMonth()->toDateString();

        $attendanceStats = [
            'total_sessions' => AttendanceSession::count(),

            'sessions_this_month' => AttendanceSession::query()
                ->whereBetween('attendance_date', [
                    $attendanceStart,
                    $attendanceEnd,
                ])
                ->count(),

            'present' => (int) $attendanceStatusCounts->get(
                StudentAttendance::STATUS_PRESENT,
                0
            ),

            'absent' => (int) $attendanceStatusCounts->get(
                StudentAttendance::STATUS_ABSENT,
                0
            ),

            'late' => (int) $attendanceStatusCounts->get(
                StudentAttendance::STATUS_LATE,
                0
            ),

            'excused' => (int) $attendanceStatusCounts->get(
                StudentAttendance::STATUS_EXCUSED,
                0
            ),
        ];

        $eligibleAttendanceRecords = $attendanceStats['present']
            + $attendanceStats['late']
            + $attendanceStats['absent'];

        $attendanceStats['rate'] = $eligibleAttendanceRecords > 0
            ? round(
                (
                    ($attendanceStats['present'] + $attendanceStats['late'])
                    / $eligibleAttendanceRecords
                ) * 100,
                2
            )
            : null;

        /*
         * Examination statistics.
         */
        $examinationStats = [
            'total' => Examination::count(),

            'upcoming' => Examination::query()
                ->whereDate('exam_date', '>=', today())
                ->count(),

            'results' => ExaminationResult::count(),
        ];

        $performance = DB::table('examination_results')
            ->join(
                'examinations',
                'examination_results.examination_id',
                '=',
                'examinations.id'
            )
            ->whereNotNull('examination_results.marks_obtained')
            ->where('examinations.maximum_marks', '>', 0)
            ->selectRaw('COUNT(*) AS graded_results')
            ->selectRaw(
                'SUM(CASE
                    WHEN examination_results.marks_obtained >= examinations.pass_marks
                    THEN 1 ELSE 0
                END) AS passed_results'
            )
            ->selectRaw(
                'AVG(
                    (examination_results.marks_obtained * 100.0)
                    / examinations.maximum_marks
                ) AS average_percentage'
            )
            ->first();

        $gradedResults = (int) $performance->graded_results;
        $passedResults = (int) $performance->passed_results;

        $examinationStats['graded_results'] = $gradedResults;
        $examinationStats['passed_results'] = $passedResults;

        $examinationStats['pass_rate'] = $gradedResults > 0
            ? round(($passedResults / $gradedResults) * 100, 2)
            : null;

        $examinationStats['average_percentage'] = $gradedResults > 0
            ? round((float) $performance->average_percentage, 2)
            : null;

        /*
         * Recent registered students.
         */
        $recentStudents = Student::query()
            ->latest()
            ->limit(5)
            ->get();

        /*
         * Recent enrollment records.
         */
        $recentEnrollments = StudentEnrollment::query()
            ->with([
                'student',
                'academicClass',
            ])
            ->latest()
            ->limit(5)
            ->get();

        /*
         * Recent attendance sessions.
         */
        $recentAttendanceSessions = AttendanceSession::query()
            ->with([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ])
            ->latest('attendance_date')
            ->limit(5)
            ->get();

        /*
         * Upcoming examinations.
         */
        $upcomingExaminations = Examination::query()
            ->with([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ])
            ->whereDate('exam_date', '>=', today())
            ->orderBy('exam_date')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'isAdmin',
            'studentStats',
            'teacherStats',
            'classStats',
            'subjectStats',
            'totalTeachingAssignments',
            'enrollmentStats',
            'attendanceStats',
            'examinationStats',
            'recentStudents',
            'recentEnrollments',
            'recentAttendanceSessions',
            'upcomingExaminations'
        ));
    }
}
