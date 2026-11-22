<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class StudentAcademicReportExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithTitle
{
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'Academic Report';
    }

    public function array(): array
    {
        $data = $this->data;
        $student = $data['student'];

        $rows = [
            [$data['institutionName']],
            ['Student Academic Report'],
            ['Generated At', $data['generatedAt']],
            [''],
            ['Student Number', $student->student_number],
            [
                'Student Name',
                $student->first_name.' '.$student->last_name,
            ],
            ['Status', $student->status],
            ['Record Status', $student->trashed() ? 'Archived' : 'Active'],
            [''],
            ['Enrollment Summary'],
            ['Total', $data['enrollmentStats']['total']],
            ['Active', $data['enrollmentStats']['active']],
            ['Completed', $data['enrollmentStats']['completed']],
            ['Withdrawn', $data['enrollmentStats']['withdrawn']],
            [''],
            ['Enrollment History'],
            ['Class', 'Code', 'Enrollment Date', 'Status'],
        ];

        foreach ($data['enrollments'] as $enrollment) {
            $rows[] = [
                $enrollment->academicClass?->name ?? 'Unavailable',
                $enrollment->academicClass?->code ?? '',
                $enrollment->enrollment_date?->format('Y-m-d') ?? '',
                $enrollment->status,
            ];
        }

        $rows[] = [''];
        $rows[] = ['Attendance Summary'];
        $rows[] = ['Status', 'Count'];

        foreach (['present', 'absent', 'late', 'excused'] as $status) {
            $rows[] = [
                ucfirst($status),
                $data['attendanceSummary']->get($status, 0),
            ];
        }

        $rows[] = [''];
        $rows[] = ['Attendance History'];
        $rows[] = [
            'Date', 'Class', 'Subject', 'Teacher',
            'Status', 'Remarks',
        ];

        foreach ($data['attendanceRecords'] as $attendance) {
            $assignment = $attendance
                ->attendanceSession?->teachingAssignment;

            $teacher = $assignment?->teacher;

            $rows[] = [
                $attendance->attendanceSession
                    ?->attendance_date?->format('Y-m-d') ?? '',
                $assignment?->academicClass?->name ?? 'Unavailable',
                $assignment?->subject?->name ?? 'Unavailable',
                $teacher
                    ? $teacher->first_name.' '.$teacher->last_name
                    : 'Unavailable',
                $attendance->status,
                $attendance->remarks ?? '',
            ];
        }

        $rows[] = [''];
        $rows[] = ['Grade Summary'];
        $rows[] = ['Grade', 'Count'];

        foreach ($data['gradeSummary'] as $grade => $count) {
            $rows[] = [$grade, $count];
        }

        $rows[] = [''];
        $rows[] = ['Examination Results'];
        $rows[] = [
            'Examination', 'Date', 'Class', 'Subject',
            'Marks', 'Maximum Marks', 'Grade', 'Remarks',
        ];

        foreach ($data['examinationResults'] as $result) {
            $exam = $result->examination;
            $assignment = $exam?->teachingAssignment;

            $rows[] = [
                $exam?->title ?? 'Unavailable',
                $exam?->exam_date?->format('Y-m-d') ?? '',
                $assignment?->academicClass?->name ?? 'Unavailable',
                $assignment?->subject?->name ?? 'Unavailable',
                $result->marks_obtained,
                $exam?->maximum_marks ?? '',
                $result->grade,
                $result->remarks ?? '',
            ];
        }

        return $rows;
    }
}
