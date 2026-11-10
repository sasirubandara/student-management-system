<?php

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\Examination;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Dashboard Test Helpers
|--------------------------------------------------------------------------
*/

function makeDashboardStatisticsContext(): array
{
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $academicClass = AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $subject = Subject::factory()->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $teachingAssignment = TeachingAssignment::factory()->create([
        'teacher_id' => $teacher->id,
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);

    return compact(
        'admin',
        'staff',
        'teacher',
        'academicClass',
        'subject',
        'teachingAssignment'
    );
}

/*
|--------------------------------------------------------------------------
| Dashboard Access
|--------------------------------------------------------------------------
*/

it('allows administrators to access the dashboard', function (): void {
    $context = makeDashboardStatisticsContext();

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Students')
        ->assertSee('Teachers')
        ->assertSee('Academic Classes')
        ->assertSee('Subjects')
        ->assertSee('Attendance Sessions')
        ->assertSee('Examinations');
});

it('allows staff users to access the dashboard', function (): void {
    $context = makeDashboardStatisticsContext();

    $response = $this->actingAs($context['staff'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard');
});

it('redirects unauthenticated guests to login', function (): void {
    auth()->logout();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

it('displays the dashboard navigation link', function (): void {
    $context = makeDashboardStatisticsContext();

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(route('dashboard'), false);
});

/*
|--------------------------------------------------------------------------
| Student and Teacher Statistics
|--------------------------------------------------------------------------
*/

it('calculates student statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    Student::factory()->create([
        'status' => Student::STATUS_INACTIVE,
    ]);

    $archivedStudent = Student::factory()->create();
    $archivedStudent->delete();

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('studentStats', function (array $stats): bool {
            return $stats['total'] === 3
                && $stats['active'] === 2
                && $stats['inactive'] === 1
                && $stats['archived'] === 1;
        });
});

it('calculates teacher statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    Teacher::factory()->create([
        'status' => Teacher::STATUS_INACTIVE,
    ]);

    $archivedTeacher = Teacher::factory()->create();
    $archivedTeacher->delete();

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('teacherStats', function (array $stats): bool {
            return $stats['total'] === 2
                && $stats['active'] === 1
                && $stats['inactive'] === 1
                && $stats['archived'] === 1;
        });
});

it('calculates academic class statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('classStats', function (array $stats): bool {
            return $stats['total'] === 2
                && $stats['active'] === 1
                && $stats['inactive'] === 1;
        });
});

it('calculates subject statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    Subject::factory()->create([
        'status' => Subject::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('subjectStats', function (array $stats): bool {
            return $stats['total'] === 2
                && $stats['active'] === 1
                && $stats['inactive'] === 1;
        });
});

/*
|--------------------------------------------------------------------------
| Enrollment and Attendance Statistics
|--------------------------------------------------------------------------
*/

it('calculates enrollment statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    StudentEnrollment::factory()->create([
        'academic_class_id' => $context['academicClass']->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    StudentEnrollment::factory()->create([
        'academic_class_id' => $context['academicClass']->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    StudentEnrollment::factory()->create([
        'academic_class_id' => $context['academicClass']->id,
        'status' => StudentEnrollment::STATUS_WITHDRAWN,
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('enrollmentStats', function (array $stats): bool {
            return $stats['total'] === 3
                && $stats['active'] === 1
                && $stats['completed'] === 1
                && $stats['withdrawn'] === 1;
        });
});

it('calculates attendance statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    $session = AttendanceSession::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'attendance_date' => now()->toDateString(),
        'created_by' => $context['admin']->id,
    ]);

    $statuses = [
        StudentAttendance::STATUS_PRESENT,
        StudentAttendance::STATUS_ABSENT,
        StudentAttendance::STATUS_LATE,
        StudentAttendance::STATUS_EXCUSED,
    ];

    foreach ($statuses as $status) {
        $enrollment = StudentEnrollment::factory()->create([
            'academic_class_id' => $context['academicClass']->id,
        ]);

        $session->studentAttendances()->create([
            'student_enrollment_id' => $enrollment->id,
            'status' => $status,
        ]);
    }

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('attendanceStats', function (array $stats): bool {
            return $stats['total_sessions'] === 1
                && $stats['sessions_this_month'] === 1
                && $stats['present'] === 1
                && $stats['absent'] === 1
                && $stats['late'] === 1
                && $stats['excused'] === 1;
        });
});

/*
|--------------------------------------------------------------------------
| Examination Statistics
|--------------------------------------------------------------------------
*/

it('calculates examination statistics correctly', function (): void {
    $context = makeDashboardStatisticsContext();

    $upcoming = Examination::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
        'exam_date' => today()->addDays(5)->toDateString(),
    ]);

    $past = Examination::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
        'exam_date' => today()->subDays(5)->toDateString(),
    ]);

    $enrollment = StudentEnrollment::factory()->create([
        'academic_class_id' => $context['academicClass']->id,
    ]);

    $upcoming->results()->create([
        'student_enrollment_id' => $enrollment->id,
        'marks_obtained' => 85,
        'grade' => 'A',
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('examinationStats', function (array $stats): bool {
            return $stats['total'] === 2
                && $stats['upcoming'] === 1
                && $stats['results'] === 1;
        });
});

it('displays upcoming examinations in date order', function (): void {
    $context = makeDashboardStatisticsContext();

    Examination::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
        'title' => 'Later Examination',
        'exam_date' => today()->addDays(10)->toDateString(),
    ]);

    Examination::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
        'title' => 'Earlier Examination',
        'exam_date' => today()->addDays(2)->toDateString(),
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('upcomingExaminations', function ($examinations): bool {
            return $examinations->count() === 2
                && $examinations->first()->title === 'Earlier Examination';
        });
});

/*
|--------------------------------------------------------------------------
| Archived Record Visibility
|--------------------------------------------------------------------------
*/

it('hides archived student and teacher counts from staff users', function (): void {
    $context = makeDashboardStatisticsContext();

    $archivedStudent = Student::factory()->create();
    $archivedStudent->delete();

    $archivedTeacher = Teacher::factory()->create();
    $archivedTeacher->delete();

    $response = $this->actingAs($context['staff'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('isAdmin', false)
        ->assertViewHas('studentStats', function (array $stats): bool {
            return $stats['archived'] === null;
        })
        ->assertViewHas('teacherStats', function (array $stats): bool {
            return $stats['archived'] === null;
        });
});

it('displays archived record counts to administrators', function (): void {
    $context = makeDashboardStatisticsContext();

    $archivedStudent = Student::factory()->create();
    $archivedStudent->delete();

    $archivedTeacher = Teacher::factory()->create();
    $archivedTeacher->delete();

    $response = $this->actingAs($context['admin'])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('isAdmin', true)
        ->assertViewHas('studentStats', function (array $stats): bool {
            return $stats['archived'] === 1;
        })
        ->assertViewHas('teacherStats', function (array $stats): bool {
            return $stats['archived'] === 1;
        });
});

/*
|--------------------------------------------------------------------------
| Empty Dashboard
|--------------------------------------------------------------------------
*/

it('displays the dashboard when no academic records exist', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('No upcoming examinations.')
        ->assertSee('No student records available.')
        ->assertSee('No enrollment records available.')
        ->assertSee('No attendance sessions available.');
});