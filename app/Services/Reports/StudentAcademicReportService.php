<?php

namespace App\Services\Reports;

use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class StudentAcademicReportService
{
    public function forExport(Student $student): array
    {
        $enrollments = $student->enrollments()
            ->with([
                'academicClass' => fn ($query) => $query->withTrashed(),
            ])
            ->orderByDesc('enrollment_date')
            ->get();

        $enrollmentIds = $enrollments->modelKeys();

        $enrollmentCounts = $enrollments
            ->countBy('status');

        $enrollmentStats = [
            'total' => $enrollments->count(),
            'active' => (int) $enrollmentCounts->get(
                StudentEnrollment::STATUS_ACTIVE, 0
            ),
            'completed' => (int) $enrollmentCounts->get(
                StudentEnrollment::STATUS_COMPLETED, 0
            ),
            'withdrawn' => (int) $enrollmentCounts->get(
                StudentEnrollment::STATUS_WITHDRAWN, 0
            ),
        ];

        $attendanceQuery = StudentAttendance::query()
            ->whereIn('student_enrollment_id', $enrollmentIds);

        $attendanceSummary = (clone $attendanceQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        $attendanceRecords = (clone $attendanceQuery)
            ->with([
                'studentEnrollment.academicClass' => fn ($query) => $query->withTrashed(),
                'attendanceSession.teachingAssignment.teacher' => fn ($query) => $query->withTrashed(),
                'attendanceSession.teachingAssignment.academicClass' => fn ($query) => $query->withTrashed(),
                'attendanceSession.teachingAssignment.subject' => fn ($query) => $query->withTrashed(),
            ])
            ->latest('id')
            ->get();

        $resultsQuery = ExaminationResult::query()
            ->whereIn('student_enrollment_id', $enrollmentIds);

        $gradeSummary = (clone $resultsQuery)
            ->select('grade', DB::raw('COUNT(*) as total'))
            ->groupBy('grade')
            ->pluck('total', 'grade')
            ->map(fn ($total) => (int) $total);

        $examinationResults = (clone $resultsQuery)
            ->with([
                'studentEnrollment.academicClass' => fn ($query) => $query->withTrashed(),
                'examination.teachingAssignment.teacher' => fn ($query) => $query->withTrashed(),
                'examination.teachingAssignment.academicClass' => fn ($query) => $query->withTrashed(),
                'examination.teachingAssignment.subject' => fn ($query) => $query->withTrashed(),
            ])
            ->latest('id')
            ->get();

        return compact(
            'student',
            'enrollments',
            'enrollmentStats',
            'attendanceSummary',
            'attendanceRecords',
            'gradeSummary',
            'examinationResults'
        );
    }
}
