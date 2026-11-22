<?php

namespace App\Services\Reports;

use App\Models\Examination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExaminationReportService
{
    /**
     * Build the filtered examination query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function examinationsQuery(array $filters): Builder
    {
        return Examination::query()
            ->with([
                'teachingAssignment.teacher' => fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.academicClass' => fn ($relation) => $relation->withTrashed(),

                'teachingAssignment.subject' => fn ($relation) => $relation->withTrashed(),
            ])
            ->withCount('results')
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
                fn (Builder $query, $date) => $query->whereDate('exam_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('exam_date', '<=', $date)
            );
    }

    /**
     * Calculate grade totals across all matching examinations.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<string, int>
     */
    public function gradeSummary(array $filters): Collection
    {
        return DB::table('examination_results')
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
                    'examinations.exam_date',
                    '>=',
                    $date
                )
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'examinations.exam_date',
                    '<=',
                    $date
                )
            )
            ->select(
                'examination_results.grade',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('examination_results.grade')
            ->orderBy('examination_results.grade')
            ->pluck('total', 'examination_results.grade')
            ->map(fn ($total) => (int) $total);
    }
}
