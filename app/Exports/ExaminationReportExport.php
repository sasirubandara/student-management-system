<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class ExaminationReportExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithTitle
{
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'Examination Report';
    }

    public function array(): array
    {
        $data = $this->data;
        $filters = $data['filters'];
        $summary = $data['gradeSummary'];

        $rows = [
            [$data['institutionName']],
            ['Examination Report'],
            ['Generated At', $data['generatedAt']],
            [''],
            ['Applied Filters'],
            ['Academic Class', $data['filterLabels']['academic_class']],
            ['Subject', $data['filterLabels']['subject']],
            ['From Date', $data['filterLabels']['start_date']],
            ['To Date', $data['filterLabels']['end_date']],
            [''],
            ['Examination Summary'],
            ['Total Examinations', $data['totalExaminations']],
            ['Total Recorded Results', $summary->sum()],
            [''],
            ['Grade Distribution'],
            ['Grade', 'Count'],
        ];

        if ($summary->isEmpty()) {
            $rows[] = ['No examination results available.'];
        } else {
            foreach ($summary as $grade => $count) {
                $rows[] = [$grade, $count];
            }
        }

        $rows[] = [''];
        $rows[] = ['Examination Records'];

        $rows[] = [
            'Examination',
            'Type',
            'Date',
            'Class',
            'Subject',
            'Teacher',
            'Maximum Marks',
            'Pass Marks',
            'Results',
        ];

        foreach ($data['examinations'] as $examination) {
            $assignment = $examination->teachingAssignment;
            $teacher = $assignment?->teacher;

            $rows[] = [
                $examination->title,
                ucwords(str_replace('_', ' ', $examination->exam_type)),
                $examination->exam_date?->format('Y-m-d') ?? '',
                $assignment?->academicClass?->name ?? 'Unavailable',
                $assignment?->subject?->name ?? 'Unavailable',
                $teacher
                    ? $teacher->first_name.' '.$teacher->last_name
                    : 'Unavailable',
                $examination->maximum_marks,
                $examination->pass_marks ?? '',
                (string) ($examination->results_count ?? 0),
            ];
        }

        if ($data['examinations']->isEmpty()) {
            $rows[] = ['No examinations match these filters.'];
        }

        return $rows;
    }
}
