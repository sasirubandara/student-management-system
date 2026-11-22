<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class AttendanceReportExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithTitle
{
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'Attendance Report';
    }

    public function array(): array
    {
        $data = $this->data;
        $filters = $data['filters'];
        $summary = $data['attendanceSummary'];

        $rows = [
            [$data['institutionName']],
            ['Attendance Report'],
            ['Generated At', $data['generatedAt']],
            [''],
            ['Applied Filters'],
            ['Academic Class', $data['filterLabels']['academic_class']],
            ['Subject', $data['filterLabels']['subject']],
            ['From Date', $data['filterLabels']['start_date']],
            ['To Date', $data['filterLabels']['end_date']],
            [''],
            ['Attendance Summary'],
            ['Total Sessions', $data['totalSessions']],
            ['Total Attendance Records', $summary->sum()],
            ['Present', $summary->get('present', 0)],
            ['Absent', $summary->get('absent', 0)],
            ['Late', $summary->get('late', 0)],
            ['Excused', $summary->get('excused', 0)],
            [''],
            ['Attendance Sessions'],
            [
                'Date',
                'Class',
                'Subject',
                'Teacher',
                'Total',
                'Present',
                'Absent',
                'Late',
                'Excused',
            ],
        ];

        foreach ($data['sessions'] as $session) {
            $assignment = $session->teachingAssignment;
            $teacher = $assignment?->teacher;

            $rows[] = [
                $session->attendance_date?->format('Y-m-d') ?? '',
                $assignment?->academicClass?->name ?? 'Unavailable',
                $assignment?->subject?->name ?? 'Unavailable',
                $teacher
                    ? $teacher->first_name.' '.$teacher->last_name
                    : 'Unavailable',
                $session->student_attendances_count,
                $session->present_count,
                $session->absent_count,
                $session->late_count,
                $session->excused_count,
            ];
        }

        if ($data['sessions']->isEmpty()) {
            $rows[] = ['No attendance sessions match these filters.'];
        }

        return $rows;
    }
}
