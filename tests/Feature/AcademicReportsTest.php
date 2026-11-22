<?php

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Reports\StudentAcademicReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Create the base records needed for academic report tests.
 */
function makeAcademicReportsTestContext(): array
{
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $teachingAssignment = TeachingAssignment::create([
        'teacher_id' => $teacher->id,
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);

    $student = Student::factory()->create([
        'student_number' => 'STU-REPORT-001',
        'first_name' => 'Report',
        'last_name' => 'Student',
    ]);

    $enrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'enrollment_date' => today()->subYear()->toDateString(),
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    return compact(
        'admin',
        'staff',
        'teacher',
        'academicClass',
        'subject',
        'teachingAssignment',
        'student',
        'enrollment'
    );
}

/**
 * Create another student enrollment in the supplied class.
 */
function createAcademicReportsTestEnrollment(
    AcademicClass $academicClass
): StudentEnrollment {
    $student = Student::factory()->create();

    return StudentEnrollment::create([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'enrollment_date' => today()->subMonths(6)->toDateString(),
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);
}

// Reports overview

it('allows staff users to view the reports overview', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.index'));

    $response->assertOk()
        ->assertSee('Academic Reports')
        ->assertSee('Student Academic Reports')
        ->assertSee('Attendance Reports')
        ->assertSee('Examination Reports');
});

it('redirects guests to login when accessing reports', function (): void {
    $this->actingAsGuest();

    $response = $this->get(route('reports.index'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

it('displays the reports navigation link to authorized staff users', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.index'));

    $response->assertOk()
        ->assertSee(route('reports.index'), false)
        ->assertSee('Reports');
});

// Student academic reports

it('allows staff users to view the student report directory', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.students.index'));

    $response->assertOk()
        ->assertSee('Student Academic Reports')
        ->assertSee($records['student']->student_number);
});

it('searches student reports by student number', function (): void {
    $records = makeAcademicReportsTestContext();

    Student::factory()->create([
        'student_number' => 'STU-REPORT-OTHER',
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.students.index', [
            'search' => 'STU-REPORT-001',
        ]));

    $response->assertOk()
        ->assertSee('STU-REPORT-001')
        ->assertDontSee('STU-REPORT-OTHER');
});

it('shows archived students in the report directory to administrators only', function (): void {
    $records = makeAcademicReportsTestContext();

    $archivedStudent = Student::factory()->create([
        'student_number' => 'STU-REPORT-ARCHIVED',
    ]);

    $archivedStudent->delete();

    $adminResponse = $this->actingAs($records['admin'])
        ->get(route('reports.students.index'));

    $adminResponse->assertOk()
        ->assertSee('STU-REPORT-ARCHIVED')
        ->assertSee('Archived');

    $staffResponse = $this->actingAs($records['staff'])
        ->get(route('reports.students.index'));

    $staffResponse->assertOk()
        ->assertDontSee('STU-REPORT-ARCHIVED');
});

it('displays student enrollment attendance and examination history', function (): void {
    $records = makeAcademicReportsTestContext();

    $session = AttendanceSession::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'attendance_date' => today()->subDays(2)->toDateString(),
        'created_by' => $records['staff']->id,
    ]);

    $session->studentAttendances()->create([
        'student_enrollment_id' => $records['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
        'remarks' => 'Report attendance test',
    ]);

    $examination = Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => 'Academic Report Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => today()->subDay()->toDateString(),
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    ExaminationResult::create([
        'examination_id' => $examination->id,
        'student_enrollment_id' => $records['enrollment']->id,
        'marks_obtained' => '90.00',
        'grade' => 'A',
        'remarks' => 'Report result test',
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.students.show', $records['student']));

    $response->assertOk()
        ->assertSee($records['student']->student_number)
        ->assertSee($records['academicClass']->name)
        ->assertSee('Enrollment History')
        ->assertSee('Attendance History')
        ->assertSee('Examination Results')
        ->assertSee('Academic Report Examination')
        ->assertSee('Report attendance test')
        ->assertSee('Report result test')
        ->assertSee('90.00')
        ->assertSee('A');
});

it('allows administrators to view archived student academic reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $response = $this->actingAs($records['admin'])
        ->get(route('reports.students.show', $records['student']->id));

    $response->assertOk()
        ->assertSee($records['student']->student_number)
        ->assertSee('Archived');
});

it('prevents staff users from viewing archived student academic reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.students.show', $records['student']->id));

    $response->assertForbidden();
});

// Attendance reports

it('allows staff users to view attendance reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance'));

    $response->assertOk()
        ->assertSee('Attendance Reports')
        ->assertSee('Attendance Summary')
        ->assertSee('Attendance Session Records');
});

it('calculates attendance totals for all four attendance statuses', function (): void {
    $records = makeAcademicReportsTestContext();

    $session = AttendanceSession::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'attendance_date' => today()->subDays(2)->toDateString(),
        'created_by' => $records['staff']->id,
    ]);

    $enrollments = [
        $records['enrollment'],
        createAcademicReportsTestEnrollment($records['academicClass']),
        createAcademicReportsTestEnrollment($records['academicClass']),
        createAcademicReportsTestEnrollment($records['academicClass']),
    ];

    $statuses = [
        StudentAttendance::STATUS_PRESENT,
        StudentAttendance::STATUS_ABSENT,
        StudentAttendance::STATUS_LATE,
        StudentAttendance::STATUS_EXCUSED,
    ];

    foreach ($statuses as $index => $status) {
        $session->studentAttendances()->create([
            'student_enrollment_id' => $enrollments[$index]->id,
            'status' => $status,
        ]);
    }

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance'));

    $response->assertOk()
        ->assertViewHas('totalSessions', 1)
        ->assertViewHas('attendanceSummary', function ($summary): bool {
            return $summary->get('present') === 1
                && $summary->get('absent') === 1
                && $summary->get('late') === 1
                && $summary->get('excused') === 1;
        });
});

it('filters attendance reports by class subject and date range', function (): void {
    $records = makeAcademicReportsTestContext();

    $targetDate = today()->subDays(2)->toDateString();

    AttendanceSession::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'attendance_date' => $targetDate,
        'created_by' => $records['staff']->id,
    ]);

    $anotherClass = AcademicClass::factory()->create();

    $anotherAssignment = TeachingAssignment::create([
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $anotherClass->id,
        'subject_id' => $records['subject']->id,
    ]);

    AttendanceSession::create([
        'teaching_assignment_id' => $anotherAssignment->id,
        'attendance_date' => $targetDate,
        'created_by' => $records['staff']->id,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
        ]));

    $response->assertOk()
        ->assertViewHas('totalSessions', 1);
});

it('rejects an attendance report date range when the end date is earlier than the start date', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-10',
        ]));

    $response->assertSessionHasErrors('end_date');
});

// Examination reports

it('allows staff users to view examination reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations'));

    $response->assertOk()
        ->assertSee('Examination Reports')
        ->assertSee('Examination Summary')
        ->assertSee('Examination Records');
});

it('calculates examination grade totals across all matching results', function (): void {
    $records = makeAcademicReportsTestContext();

    $examination = Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => 'Grade Summary Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => today()->subDays(2)->toDateString(),
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    $enrollments = [
        $records['enrollment'],
        createAcademicReportsTestEnrollment($records['academicClass']),
        createAcademicReportsTestEnrollment($records['academicClass']),
    ];

    $grades = ['A', 'A', 'B'];

    foreach ($grades as $index => $grade) {
        ExaminationResult::create([
            'examination_id' => $examination->id,
            'student_enrollment_id' => $enrollments[$index]->id,
            'marks_obtained' => $index === 2 ? '75.00' : '90.00',
            'grade' => $grade,
        ]);
    }

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations'));

    $response->assertOk()
        ->assertViewHas('totalExaminations', 1)
        ->assertViewHas('gradeSummary', function ($summary): bool {
            return $summary->get('A') === 2
                && $summary->get('B') === 1;
        });
});

it('filters examination reports by class subject and date range', function (): void {
    $records = makeAcademicReportsTestContext();

    $targetDate = today()->subDays(2)->toDateString();

    Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => 'Filtered Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => $targetDate,
        'maximum_marks' => '100.00',
        'created_by' => $records['staff']->id,
    ]);

    $anotherClass = AcademicClass::factory()->create();

    $anotherSubject = Subject::factory()->create();

    $anotherAssignment = TeachingAssignment::create([
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $anotherClass->id,
        'subject_id' => $anotherSubject->id,
    ]);

    Examination::create([
        'teaching_assignment_id' => $anotherAssignment->id,
        'title' => 'Unmatched Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => $targetDate,
        'maximum_marks' => '100.00',
        'created_by' => $records['staff']->id,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
        ]));

    $response->assertOk()
        ->assertViewHas('totalExaminations', 1)
        ->assertSee('Filtered Examination')
        ->assertDontSee('Unmatched Examination');
});

it('rejects an examination report date range when the end date is earlier than the start date', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-10',
        ]));

    $response->assertSessionHasErrors('end_date');
});

// Empty report results

it('displays empty states when attendance and examination records do not exist', function (): void {
    $records = makeAcademicReportsTestContext();

    $attendanceResponse = $this->actingAs($records['staff'])
        ->get(route('reports.attendance'));

    $attendanceResponse->assertOk()
        ->assertSee('No attendance sessions match these filters.');

    $examinationResponse = $this->actingAs($records['staff'])
        ->get(route('reports.examinations'));

    $examinationResponse->assertOk()
        ->assertSee('No examinations match these filters.');
});

// Student academic report PDF export

it('allows staff to download a student academic report PDF', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.pdf',
            $records['student']
        ));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(substr($response->getContent(), 0, 5))
        ->toBe('%PDF-');
});

it('requires authentication to export student reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAsGuest()
        ->get(route(
            'reports.students.export.pdf',
            $records['student']
        ))
        ->assertRedirect(route('login'));
});

it('allows administrators to export archived student reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $response = $this->actingAs($records['admin'])
        ->get(route(
            'reports.students.export.pdf',
            $records['student']->id
        ));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(substr($response->getContent(), 0, 5))
        ->toBe('%PDF-');
});

it('prevents staff from exporting archived student reports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.pdf',
            $records['student']->id
        ))
        ->assertForbidden();
});

it('records successful student PDF exports in the audit log', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.pdf',
            $records['student']
        ))
        ->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $records['staff']->id,
        'event' => 'reports.student.exported',
        'auditable_id' => $records['student']->id,
    ]);
});

it('does not audit unauthorized student PDF exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.pdf',
            $records['student']->id
        ))
        ->assertForbidden();

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'reports.student.exported',
        'auditable_id' => $records['student']->id,
    ]);
});

it('loads the complete attendance history for student PDF exports', function (): void {
    $records = makeAcademicReportsTestContext();

    for ($i = 1; $i <= 12; $i++) {
        $session = AttendanceSession::create([
            'teaching_assignment_id' => $records['teachingAssignment']->id,
            'attendance_date' => today()->subDays($i)->toDateString(),
            'created_by' => $records['staff']->id,
        ]);

        StudentAttendance::create([
            'attendance_session_id' => $session->id,
            'student_enrollment_id' => $records['enrollment']->id,
            'status' => StudentAttendance::STATUS_PRESENT,
        ]);
    }

    $browserResponse = $this->actingAs($records['staff'])
        ->get(route('reports.students.show', $records['student']));

    $browserResponse->assertOk()
        ->assertViewHas('attendanceRecords', function ($records): bool {
            return $records->count() === 10
                && $records->total() === 12;
        });

    $report = app(
        StudentAcademicReportService::class
    )->forExport($records['student']);

    expect($report['attendanceRecords'])->toHaveCount(12)
        ->and($report['attendanceSummary']->get('present'))
        ->toBe(12);
});

it('loads the complete examination history for student PDF exports', function (): void {
    $records = makeAcademicReportsTestContext();

    for ($i = 1; $i <= 12; $i++) {
        $examination = Examination::create([
            'teaching_assignment_id' => $records['teachingAssignment']->id,
            'title' => 'Export Examination '.$i,
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => today()->subDays($i)->toDateString(),
            'maximum_marks' => '100.00',
            'pass_marks' => '40.00',
            'created_by' => $records['staff']->id,
        ]);

        ExaminationResult::create([
            'examination_id' => $examination->id,
            'student_enrollment_id' => $records['enrollment']->id,
            'marks_obtained' => '90.00',
            'grade' => 'A',
        ]);
    }

    $browserResponse = $this->actingAs($records['staff'])
        ->get(route('reports.students.show', $records['student']));

    $browserResponse->assertOk()
        ->assertViewHas('examinationResults', function ($results): bool {
            return $results->count() === 10
                && $results->total() === 12;
        });

    $report = app(
        StudentAcademicReportService::class
    )->forExport($records['student']);

    expect($report['examinationResults'])->toHaveCount(12)
        ->and($report['gradeSummary']->get('A'))
        ->toBe(12);
});

// Attendance report PDF export

it('allows staff to download an attendance report PDF', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance.export.pdf'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader(
            'Content-Disposition',
            'attachment; filename="attendance-report.pdf"'
        );

    expect(substr($response->getContent(), 0, 5))
        ->toBe('%PDF-');
});

// Examination report PDF export

it('allows staff to download an examination report PDF', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations.export.pdf'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader(
            'Content-Disposition',
            'attachment; filename="examination-report.pdf"'
        );

    expect(substr($response->getContent(), 0, 5))
        ->toBe('%PDF-');
});

// Student academic report Excel export

it('exports a valid student academic Excel workbook', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.excel',
            $records['student']
        ));

    $response->assertOk()
        ->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

    $content = $response->getContent();

    expect(substr($content, 0, 2))->toBe('PK');

    $path = tempnam(sys_get_temp_dir(), 'sms_xlsx_');

    try {
        file_put_contents($path, $content);

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();

            expect($sheet->getCell('A2')->getValue())
                ->toBe('Student Academic Report');

            expect($sheet->getCell('A5')->getValue())
                ->toBe('Student Number');

            expect($sheet->getCell('B5')->getValue())
                ->toBe($records['student']->student_number);

            expect($sheet->getCell('B5')->getDataType())
                ->toBe(DataType::TYPE_STRING);
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

// Attendance report Excel export

it('exports a valid filtered attendance Excel workbook', function (): void {
    $records = makeAcademicReportsTestContext();

    $date = today()->subDays(2)->toDateString();

    $session = AttendanceSession::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'attendance_date' => $date,
        'created_by' => $records['staff']->id,
    ]);

    $session->studentAttendances()->create([
        'student_enrollment_id' => $records['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance.export.excel', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
            'start_date' => $date,
            'end_date' => $date,
        ]));

    $response->assertOk()
        ->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

    expect(substr($response->getContent(), 0, 2))
        ->toBe('PK');

    $path = tempnam(sys_get_temp_dir(), 'sms_attendance_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();
            $rows = $sheet->toArray();

            expect($sheet->getCell('A2')->getValue())
                ->toBe('Attendance Report');

            expect($rows)->toContain([
                'Present', '1', null, null, null,
                null, null, null, null,
            ]);

            // Locate the data row by its date rather than
            // relying on a hard-coded worksheet row number.
            $matchingRows = array_filter(
                $rows,
                fn (array $row): bool => ($row[0] ?? null) === $date
            );

            expect($matchingRows)->toHaveCount(1);

            $dataRow = array_values($matchingRows)[0];

            expect($dataRow[1])
                ->toBe($records['academicClass']->name);

            expect($dataRow[2])
                ->toBe($records['subject']->name);

            expect($dataRow[4])->toBe('1');
            expect($dataRow[5])->toBe('1');
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

it('includes readable class and subject names in attendance Excel filters', function (): void {
    $records = makeAcademicReportsTestContext();

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.attendance.export.excel', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]));

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'sms_labels_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();

            $rows = $sheet->toArray();

            expect($rows)->toContain([
                'Academic Class',
                $records['academicClass']->name,
                null, null, null, null, null, null, null,
            ]);

            expect($rows)->toContain([
                'Subject',
                $records['subject']->name,
                null, null, null, null, null, null, null,
            ]);
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

// Examination report Excel export

it('exports a valid filtered examination Excel workbook', function (): void {
    $records = makeAcademicReportsTestContext();

    $date = today()->subDays(2)->toDateString();

    Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => 'Excel Export Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => $date,
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations.export.excel', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
            'start_date' => $date,
            'end_date' => $date,
        ]));

    $response->assertOk()
        ->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

    expect(substr($response->getContent(), 0, 2))
        ->toBe('PK');

    $path = tempnam(sys_get_temp_dir(), 'sms_examination_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();

            expect($sheet->getCell('A2')->getValue())
                ->toBe('Examination Report');

            $rows = $sheet->toArray();

            $matchingRows = array_filter(
                $rows,
                fn (array $row): bool => ($row[0] ?? null) === 'Excel Export Examination'
            );

            expect($matchingRows)->toHaveCount(1);

            $dataRow = array_values($matchingRows)[0];

            expect($dataRow[1])->toBe('Quiz');
            expect($dataRow[2])->toBe($date);
            expect($dataRow[3])
                ->toBe($records['academicClass']->name);
            expect($dataRow[4])
                ->toBe($records['subject']->name);
            expect($dataRow[8])->toBe('0');
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

// Export authentication and filter validation

it('requires authentication for all academic report export formats', function (): void {
    $records = makeAcademicReportsTestContext();

    // Ensure the requests are made as a guest.
    auth()->logout();

    $urls = [
        route('reports.students.export.pdf', $records['student']),
        route('reports.students.export.excel', $records['student']),
        route('reports.attendance.export.pdf'),
        route('reports.attendance.export.excel'),
        route('reports.examinations.export.pdf'),
        route('reports.examinations.export.excel'),
    ];

    foreach ($urls as $url) {
        $this->get($url)
            ->assertRedirect(route('login'));
    }
});

it('rejects invalid date ranges for attendance exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $formats = [
        'reports.attendance.export.pdf',
        'reports.attendance.export.excel',
    ];

    foreach ($formats as $routeName) {
        $response = $this->get(route($routeName, [
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-10',
        ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }
});

it('rejects invalid date ranges for examination exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $formats = [
        'reports.examinations.export.pdf',
        'reports.examinations.export.excel',
    ];

    foreach ($formats as $routeName) {
        $response = $this->get(route($routeName, [
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-10',
        ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }
});

// Archived student academic report Excel export

it('allows administrators to export archived student reports as Excel', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $response = $this->actingAs($records['admin'])
        ->get(route(
            'reports.students.export.excel',
            $records['student']->id
        ));

    $response->assertOk()
        ->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

    expect(substr($response->getContent(), 0, 2))
        ->toBe('PK');
});

it('prevents staff from exporting archived student reports as Excel', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.excel',
            $records['student']->id
        ))
        ->assertForbidden();
});

it('does not audit unauthorized student Excel exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $records['student']->delete();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.excel',
            $records['student']->id
        ))
        ->assertForbidden();

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'reports.student.exported',
        'auditable_id' => $records['student']->id,
    ]);
});

// Export audit logging

it('records successful student Excel exports in the audit log', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.excel',
            $records['student']
        ))
        ->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $records['staff']->id,
        'event' => 'reports.student.exported',
        'auditable_id' => $records['student']->id,
    ]);
});

it('records successful attendance PDF and Excel exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $routes = [
        'reports.attendance.export.pdf',
        'reports.attendance.export.excel',
    ];

    foreach ($routes as $routeName) {
        $this->get(route($routeName))
            ->assertOk();
    }

    $matchingAuditCount = DB::table('audit_logs')
        ->where('user_id', $records['staff']->id)
        ->where('event', 'reports.attendance.exported')
        ->count();

    expect($matchingAuditCount)->toBe(2);
});

it('records successful examination PDF and Excel exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $routes = [
        'reports.examinations.export.pdf',
        'reports.examinations.export.excel',
    ];

    foreach ($routes as $routeName) {
        $this->get(route($routeName))
            ->assertOk();
    }

    $matchingAuditCount = DB::table('audit_logs')
        ->where('user_id', $records['staff']->id)
        ->where('event', 'reports.examinations.exported')
        ->count();

    expect($matchingAuditCount)->toBe(2);
});

it('does not audit unsuccessful exports with invalid filters', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $routes = [
        'reports.attendance.export.pdf',
        'reports.attendance.export.excel',
        'reports.examinations.export.pdf',
        'reports.examinations.export.excel',
    ];

    $beforeCount = DB::table('audit_logs')
        ->where('user_id', $records['staff']->id)
        ->whereIn('event', [
            'reports.attendance.exported',
            'reports.examinations.exported',
        ])
        ->count();

    foreach ($routes as $routeName) {
        $this->get(route($routeName, [
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-10',
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }

    $afterCount = DB::table('audit_logs')
        ->where('user_id', $records['staff']->id)
        ->whereIn('event', [
            'reports.attendance.exported',
            'reports.examinations.exported',
        ])
        ->count();

    expect($afterCount)->toBe($beforeCount);
});

it('records correct audit metadata for all six academic report exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $exports = [
        [
            'route' => 'reports.students.export.pdf',
            'parameters' => ['student' => $records['student']->id],
            'event' => 'reports.student.exported',
            'format' => 'pdf',
            'type' => 'student_academic',
        ],
        [
            'route' => 'reports.students.export.excel',
            'parameters' => ['student' => $records['student']->id],
            'event' => 'reports.student.exported',
            'format' => 'xlsx',
            'type' => 'student_academic',
        ],
        [
            'route' => 'reports.attendance.export.pdf',
            'parameters' => [],
            'event' => 'reports.attendance.exported',
            'format' => 'pdf',
            'type' => 'attendance',
        ],
        [
            'route' => 'reports.attendance.export.excel',
            'parameters' => [],
            'event' => 'reports.attendance.exported',
            'format' => 'xlsx',
            'type' => 'attendance',
        ],
        [
            'route' => 'reports.examinations.export.pdf',
            'parameters' => [],
            'event' => 'reports.examinations.exported',
            'format' => 'pdf',
            'type' => 'examinations',
        ],
        [
            'route' => 'reports.examinations.export.excel',
            'parameters' => [],
            'event' => 'reports.examinations.exported',
            'format' => 'xlsx',
            'type' => 'examinations',
        ],
    ];

    $lastAuditId = (int) DB::table('audit_logs')->max('id');

    foreach ($exports as $export) {
        $response = $this->get(route(
            $export['route'],
            $export['parameters']
        ));

        $response->assertOk();

        unset($response);

        $audit = DB::table('audit_logs')
            ->where('id', '>', $lastAuditId)
            ->where('user_id', $records['staff']->id)
            ->where('event', $export['event'])
            ->orderByDesc('id')
            ->first();

        expect($audit)
            ->not->toBeNull();

        $properties = json_decode(
            $audit->properties,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        expect($properties)
            ->toBeArray()
            ->and($properties['format'] ?? null)
            ->toBe($export['format'])
            ->and($properties['report_type'] ?? null)
            ->toBe($export['type']);

        if ($export['type'] === 'student_academic') {
            expect((int) $audit->auditable_id)
                ->toBe($records['student']->id);
        }

        $lastAuditId = $audit->id;

        gc_collect_cycles();
    }
});

it('exports all attendance sessions beyond browser pagination', function (): void {
    $records = makeAcademicReportsTestContext();

    $dates = [];

    // The browser report displays 15 sessions per page.
    // Create 17 sessions to verify export completeness.
    for ($i = 1; $i <= 17; $i++) {
        $date = today()->subDays($i)->toDateString();

        $dates[] = $date;

        AttendanceSession::create([
            'teaching_assignment_id' => $records['teachingAssignment']->id,
            'attendance_date' => $date,
            'created_by' => $records['staff']->id,
        ]);
    }

    $browserResponse = $this->actingAs($records['staff'])
        ->get(route('reports.attendance'));

    $browserResponse->assertOk()
        ->assertViewHas('sessions', function ($sessions): bool {
            return $sessions->count() === 15
                && $sessions->total() === 17;
        });

    $response = $this->get(
        route('reports.attendance.export.excel')
    );

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'sms_attendance_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $rows = $workbook->getActiveSheet()->toArray();

            $exportedDates = array_column($rows, 0);

            foreach ($dates as $date) {
                expect($exportedDates)->toContain($date);
            }

            $matchingDates = array_filter(
                $exportedDates,
                fn ($value): bool => in_array(
                    $value,
                    $dates,
                    true
                )
            );

            expect($matchingDates)->toHaveCount(17);
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

it('excludes unrelated examinations from filtered Excel exports', function (): void {
    $records = makeAcademicReportsTestContext();

    $date = today()->subDays(2)->toDateString();

    Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => 'Matching Excel Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => $date,
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    $otherClass = AcademicClass::factory()->create();

    $otherAssignment = TeachingAssignment::create([
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $otherClass->id,
        'subject_id' => $records['subject']->id,
    ]);

    Examination::create([
        'teaching_assignment_id' => $otherAssignment->id,
        'title' => 'Unrelated Excel Examination',
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => $date,
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations.export.excel', [
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
            'start_date' => $date,
            'end_date' => $date,
        ]));

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'sms_exam_filter_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $rows = $workbook->getActiveSheet()->toArray();

            $titles = array_column($rows, 0);

            expect($titles)
                ->toContain('Matching Excel Examination')
                ->not->toContain('Unrelated Excel Examination');
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

it('exports formula-like examination titles as plain text', function (): void {
    $records = makeAcademicReportsTestContext();

    $title = '=1+1';

    Examination::create([
        'teaching_assignment_id' => $records['teachingAssignment']->id,
        'title' => $title,
        'exam_type' => Examination::TYPE_QUIZ,
        'exam_date' => today()->subDay()->toDateString(),
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
        'created_by' => $records['staff']->id,
    ]);

    $response = $this->actingAs($records['staff'])
        ->get(route('reports.examinations.export.excel'));

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'sms_formula_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();
            $rows = $sheet->toArray();

            $matchingRows = array_filter(
                $rows,
                fn (array $row): bool => ($row[0] ?? null) === $title
            );

            expect($matchingRows)->toHaveCount(1);

            $rowNumber = array_key_first($matchingRows) + 1;

            $cell = $sheet->getCell('A'.$rowNumber);

            expect($cell->getValue())
                ->toBe($title);

            expect($cell->getDataType())
                ->toBe(DataType::TYPE_STRING);
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

it('exports formula-like student names as plain text', function (): void {
    $records = makeAcademicReportsTestContext();

    $student = $records['student'];

    $student->first_name = '=1+1';
    $student->save();

    $expectedName = '=1+1 '.$student->last_name;

    $response = $this->actingAs($records['staff'])
        ->get(route(
            'reports.students.export.excel',
            $student
        ));

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'sms_student_formula_');

    try {
        file_put_contents($path, $response->getContent());

        $workbook = IOFactory::load($path);

        try {
            $sheet = $workbook->getActiveSheet();
            $rows = $sheet->toArray();

            $studentNameRow = null;

            foreach ($rows as $index => $row) {
                if (($row[0] ?? null) === 'Student Name') {
                    $studentNameRow = $index + 1;
                    break;
                }
            }

            expect($studentNameRow)->not->toBeNull();

            $cell = $sheet->getCell('B'.$studentNameRow);

            expect($cell->getValue())
                ->toBe($expectedName);

            expect($cell->getDataType())
                ->toBe(DataType::TYPE_STRING);
        } finally {
            $workbook->disconnectWorksheets();
        }
    } finally {
        unlink($path);
    }
});

it('passes all attendance sessions to the PDF renderer beyond pagination', function (): void {
    $records = makeAcademicReportsTestContext();

    $dates = [];

    for ($i = 1; $i <= 17; $i++) {
        $date = today()->subDays($i)->toDateString();

        $dates[] = $date;

        AttendanceSession::create([
            'teaching_assignment_id' => $records['teachingAssignment']->id,
            'attendance_date' => $date,
            'created_by' => $records['staff']->id,
        ]);
    }

    $this->actingAs($records['staff'])
        ->get(route('reports.attendance'))
        ->assertOk()
        ->assertViewHas('sessions', function ($sessions): bool {
            return $sessions->count() === 15
                && $sessions->total() === 17;
        });

    // Capture the data passed to DomPDF without inspecting
    // compressed PDF binary content.
    $pdf = Mockery::mock(Barryvdh\DomPDF\PDF::class);

    $pdf->shouldReceive('setPaper')
        ->once()
        ->with('a4', 'landscape')
        ->andReturnSelf();

    $pdf->shouldReceive('output')
        ->once()
        ->andReturn('%PDF-1.4 test fixture');

    Pdf::shouldReceive('loadView')
        ->once()
        ->with(
            'reports.exports.attendance-pdf',
            Mockery::on(function (array $data) use ($dates): bool {
                $sessions = $data['sessions'] ?? null;

                if (! $sessions instanceof Collection) {
                    return false;
                }

                $exportedDates = $sessions
                    ->map(fn ($session) => $session->attendance_date->format('Y-m-d')
                    )
                    ->all();

                return $sessions->count() === 17
                    && $data['totalSessions'] === 17
                    && count(array_intersect($dates, $exportedDates)) === 17;
            })
        )
        ->andReturn($pdf);

    $this->get(route('reports.attendance.export.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('passes all examinations to the PDF renderer beyond pagination', function (): void {
    $records = makeAcademicReportsTestContext();

    $titles = [];

    for ($i = 1; $i <= 17; $i++) {
        $title = 'PDF Completeness Examination '.$i;

        $titles[] = $title;

        Examination::create([
            'teaching_assignment_id' => $records['teachingAssignment']->id,
            'title' => $title,
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => today()->subDays($i)->toDateString(),
            'maximum_marks' => '100.00',
            'pass_marks' => '40.00',
            'created_by' => $records['staff']->id,
        ]);
    }

    $this->actingAs($records['staff'])
        ->get(route('reports.examinations'))
        ->assertOk()
        ->assertViewHas('examinations', function ($examinations): bool {
            return $examinations->count() === 15
                && $examinations->total() === 17;
        });

    $pdf = Mockery::mock(Barryvdh\DomPDF\PDF::class);

    $pdf->shouldReceive('setPaper')
        ->once()
        ->with('a4', 'landscape')
        ->andReturnSelf();

    $pdf->shouldReceive('output')
        ->once()
        ->andReturn('%PDF-1.4 test fixture');

    Pdf::shouldReceive('loadView')
        ->once()
        ->with(
            'reports.exports.examination-pdf',
            Mockery::on(function (array $data) use ($titles): bool {
                $examinations = $data['examinations'] ?? null;

                if (! $examinations instanceof Collection) {
                    return false;
                }

                $exportedTitles = $examinations
                    ->pluck('title')
                    ->all();

                return $examinations->count() === 17
                    && $data['totalExaminations'] === 17
                    && count(array_intersect($titles, $exportedTitles)) === 17;
            })
        )
        ->andReturn($pdf);

    $this->get(route('reports.examinations.export.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('exports valid PDF and Excel reports when no academic records exist', function (): void {
    $records = makeAcademicReportsTestContext();

    $this->actingAs($records['staff']);

    $pdfRoutes = [
        'reports.attendance.export.pdf',
        'reports.examinations.export.pdf',
    ];

    foreach ($pdfRoutes as $routeName) {
        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        expect(substr($response->getContent(), 0, 5))
            ->toBe('%PDF-');
    }

    $excelRoutes = [
        'reports.attendance.export.excel' => 'No attendance sessions match these filters.',
        'reports.examinations.export.excel' => 'No examinations match these filters.',
    ];

    foreach ($excelRoutes as $routeName => $emptyMessage) {
        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $path = tempnam(sys_get_temp_dir(), 'sms_empty_');

        try {
            file_put_contents($path, $response->getContent());

            $workbook = IOFactory::load($path);

            try {
                $rows = $workbook->getActiveSheet()->toArray();

                $firstColumn = array_column($rows, 0);

                expect($firstColumn)->toContain($emptyMessage);
            } finally {
                $workbook->disconnectWorksheets();
            }
        } finally {
            unlink($path);
        }
    }
});
