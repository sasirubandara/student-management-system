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