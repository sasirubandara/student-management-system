<?php

namespace App\Services\Reports;

use App\Models\AcademicClass;
use App\Models\Subject;
use Illuminate\Http\Request;

class ReportFilterService
{
    /**
     * Validate filters shared by attendance and examination reports.
     *
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        return $request->validate([
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
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ]);
    }

    /**
     * Resolve validated report filters into readable labels.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function displayLabels(array $filters): array
    {
        $className = 'All Classes';
        $subjectName = 'All Subjects';

        if (! empty($filters['academic_class_id'])) {
            $class = AcademicClass::withTrashed()
                ->find($filters['academic_class_id']);

            $className = $class?->name ?? 'Unavailable';
        }

        if (! empty($filters['subject_id'])) {
            $subject = Subject::withTrashed()
                ->find($filters['subject_id']);

            $subjectName = $subject?->name ?? 'Unavailable';
        }

        return [
            'academic_class' => $className,
            'subject' => $subjectName,
            'start_date' => $filters['start_date'] ?? 'Any',
            'end_date' => $filters['end_date'] ?? 'Any',
        ];
    }
}
