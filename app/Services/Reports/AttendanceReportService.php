<?php

namespace App\Services\Reports;

use App\Models\AttendanceSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    /**
     * Build the filtered attendance session query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function sessionsQuery(array $filters): Builder
    {
        return AttendanceSession::query()
            ->with([
                'teachingAssignment.teacher' => fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.academicClass' => fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.subject' => fn ($relation) => $relation->withTrashed(),
            ])
            ->withCount([
                'studentAttendances',

                'studentAttendances as present_count' => fn (Builder $query) => $query->where('status', 'present'),

                'studentAttendances as absent_count' => fn (Builder $query) => $query->where('status', 'absent'),

                'studentAttendances as late_count' => fn (Builder $query) => $query->where('status', 'late'),

                'studentAttendances as excused_count' => fn (Builder $query) => $query->where('status', 'excused'),
            ])
            ->whereHas(
                'teachingAssignment',
                function (Builder $query) use ($filters): void {
                    $query
                        ->when(
                            $filters['academic_class_id'] ?? null,
                            fn (Builder $query, $id) => $query->where('academic_class_id', $id)
                        )
                        ->when(
                            $filters['subject_id'] ?? null,
                            fn (Builder $query, $id) => $query->where('subject_id', $id)
                        );
                }
            )
            ->when(
                $filters['start_date'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('attendance_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('attendance_date', '<=', $date)
            );
    }

    /**
     * Calculate attendance totals across all matching sessions.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<string, int>
     */
    public function attendanceSummary(array $filters): Collection
    {
        return DB::table('student_attendances')
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
                fn ($query, $id) => $query->where(
                    'teaching_assignments.academic_class_id',
                    $id
                )
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn ($query, $id) => $query->where(
                    'teaching_assignments.subject_id',
                    $id
                )
            )
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'attendance_sessions.attendance_date',
                    '>=',
                    $date
                )
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'attendance_sessions.attendance_date',
                    '<=',
                    $date
                )
            )
            ->select(
                'student_attendances.status',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('student_attendances.status')
            ->pluck('total', 'student_attendances.status')
            ->map(fn ($total) => (int) $total);
    }
}
