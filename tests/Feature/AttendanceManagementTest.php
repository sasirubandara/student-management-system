<?php

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Attendance Test Helpers
|--------------------------------------------------------------------------
*/

function makeAttendanceManagementContext(): array
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

    $student = Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
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

function makeAttendanceManagementSession(
    array $context,
    ?string $date = null
): AttendanceSession {
    return AttendanceSession::factory()->create([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'attendance_date' => $date ?? now()->toDateString(),
        'created_by' => $context['admin']->id,
    ]);
}

function makeAttendanceManagementEnrollment(
    AcademicClass $academicClass,
    array $studentAttributes = [],
    array $enrollmentAttributes = []
): array {
    $student = Student::factory()->create(array_merge([
        'status' => Student::STATUS_ACTIVE,
    ], $studentAttributes));

    $enrollment = StudentEnrollment::factory()->create(array_merge([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ], $enrollmentAttributes));

    return compact('student', 'enrollment');
}

/*
|--------------------------------------------------------------------------
| Attendance Session Listing and Viewing
|--------------------------------------------------------------------------
*/

it('allows administrators to view the attendance session directory', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index'));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['academicClass']->name)
        ->assertSee($context['subject']->name)
        ->assertSee($session->attendance_date->format('d F Y'));
});

it('allows staff users to view the attendance session directory', function (): void {
    $context = makeAttendanceManagementContext();

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['staff'])
        ->get(route('attendance-sessions.index'));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['subject']->name);
});

it('displays the attendance navigation link to authorized users', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['staff'])
        ->get(route('attendance-sessions.index'));

    $response->assertOk()
        ->assertSee(route('attendance-sessions.index'), false);
});

it('displays the attendance session creation link to authorized users', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index'));

    $response->assertOk()
        ->assertSee(route('attendance-sessions.create'), false);
});

it('allows administrators and staff to view attendance session details', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['staff'])
        ->get(route('attendance-sessions.show', $session));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['academicClass']->name)
        ->assertSee($context['subject']->name)
        ->assertSee($context['student']->student_number);
});

it('searches attendance sessions by teacher number', function (): void {
    $context = makeAttendanceManagementContext();

    $context['teacher']->update([
        'teacher_number' => 'TCH-ATTENDANCE-SEARCH-001',
    ]);

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'search' => 'TCH-ATTENDANCE-SEARCH-001',
        ]));

    $response->assertOk()
        ->assertSee('TCH-ATTENDANCE-SEARCH-001');
});

it('searches attendance sessions by academic class code', function (): void {
    $context = makeAttendanceManagementContext();

    $context['academicClass']->update([
        'code' => 'CLS-ATTENDANCE-SEARCH-001',
    ]);

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'search' => 'CLS-ATTENDANCE-SEARCH-001',
        ]));

    $response->assertOk()
        ->assertSee('CLS-ATTENDANCE-SEARCH-001');
});

it('searches attendance sessions by subject code', function (): void {
    $context = makeAttendanceManagementContext();

    $context['subject']->update([
        'code' => 'SUB-ATTENDANCE-SEARCH-001',
    ]);

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'search' => 'SUB-ATTENDANCE-SEARCH-001',
        ]));

    $response->assertOk()
        ->assertSee('SUB-ATTENDANCE-SEARCH-001');
});

it('paginates attendance sessions with ten records per page', function (): void {
    $context = makeAttendanceManagementContext();

    for ($i = 0; $i < 11; $i++) {
        makeAttendanceManagementSession(
            $context,
            now()->subDays($i)->toDateString()
        );
    }

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index'));

    $response->assertOk()
        ->assertViewHas('attendanceSessions', function ($sessions): bool {
            return $sessions->total() === 11
                && $sessions->perPage() === 10;
        });
});

/*
|--------------------------------------------------------------------------
| Attendance Session Creation
|--------------------------------------------------------------------------
*/

it('allows administrators to view the attendance session creation form', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.create'));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['academicClass']->name)
        ->assertSee($context['subject']->name);
});

it('allows staff users to view the attendance session creation form', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['staff'])
        ->get(route('attendance-sessions.create'));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number);
});

it('allows administrators to create attendance sessions', function (): void {
    $context = makeAttendanceManagementContext();

    $attendanceDate = now()->toDateString();

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => $attendanceDate,
        ]);

    $session = AttendanceSession::query()
        ->where('teaching_assignment_id', $context['teachingAssignment']->id)
        ->whereDate('attendance_date', $attendanceDate)
        ->firstOrFail();

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('attendance_sessions', [
        'id' => $session->id,
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
    ]);

    expect($session->fresh()->attendance_date->toDateString())
        ->toBe($attendanceDate);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'attendance.session_created',
        'auditable_type' => $session->getMorphClass(),
        'auditable_id' => $session->id,
        'user_id' => $context['admin']->id,
    ]);
});

it('allows staff users to create attendance sessions', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['staff'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('attendance_sessions', [
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['staff']->id,
    ]);

    $session = AttendanceSession::query()
        ->where('teaching_assignment_id', $context['teachingAssignment']->id)
        ->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'attendance.session_created',
        'auditable_type' => $session->getMorphClass(),
        'auditable_id' => $session->id,
        'user_id' => $context['staff']->id,
    ]);
});

it('notifies other active users when an attendance session is created', function (): void {
    $context = makeAttendanceManagementContext();

    $inactiveUser = User::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $attendanceSession = AttendanceSession::query()
        ->where('teaching_assignment_id', $context['teachingAssignment']->id)
        ->whereDate('attendance_date', now()->toDateString())
        ->firstOrFail();

    $response->assertRedirect(
        route('attendance-sessions.show', $attendanceSession)
    );

    // Another active user should receive the notification.
    $notification = $context['staff']->notifications()
        ->where('type', 'attendance.session_created')
        ->firstOrFail();

    expect($notification->data['event'])
        ->toBe('attendance.session_created');

    expect($notification->data['url'])
        ->toBe(route('attendance-sessions.show', $attendanceSession));

    // The actor should not receive their own notification.
    expect(
        $context['admin']->notifications()
            ->where('type', 'attendance.session_created')
            ->exists()
    )->toBeFalse();

    // Inactive users must not receive notifications.
    expect(
        $inactiveUser->notifications()
            ->where('type', 'attendance.session_created')
            ->exists()
    )->toBeFalse();
});

it('rejects duplicate sessions for the same teaching assignment and date', function (): void {
    $context = makeAttendanceManagementContext();

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');

    $this->assertDatabaseCount('attendance_sessions', 1);
});

it('does not notify users when a duplicate attendance session is rejected', function (): void {
    $context = makeAttendanceManagementContext();

    makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');

    expect(
        $context['staff']->notifications()
            ->where('type', 'attendance.session_created')
            ->count()
    )->toBe(0);
});

it('rejects future attendance dates', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->addDay()->toDateString(),
        ]);

    $response->assertSessionHasErrors('attendance_date');

    $this->assertDatabaseCount('attendance_sessions', 0);
});

it('requires a teaching assignment and attendance date', function (): void {
    $context = makeAttendanceManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), []);

    $response->assertSessionHasErrors([
        'teaching_assignment_id',
        'attendance_date',
    ]);
});

it('rejects attendance sessions for inactive teachers', function (): void {
    $context = makeAttendanceManagementContext();

    $context['teacher']->update([
        'status' => Teacher::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');

    $this->assertDatabaseCount('attendance_sessions', 0);
});

it('rejects attendance sessions for inactive academic classes', function (): void {
    $context = makeAttendanceManagementContext();

    $context['academicClass']->update([
        'status' => AcademicClass::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');

    $this->assertDatabaseCount('attendance_sessions', 0);
});

it('rejects attendance sessions for inactive subjects', function (): void {
    $context = makeAttendanceManagementContext();

    $context['subject']->update([
        'status' => Subject::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('attendance-sessions.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'attendance_date' => now()->toDateString(),
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');

    $this->assertDatabaseCount('attendance_sessions', 0);
});

/*
|--------------------------------------------------------------------------
| Attendance Session Removal
|--------------------------------------------------------------------------
*/

it('allows administrators to remove an empty attendance session', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->delete(route('attendance-sessions.destroy', $session));

    $response->assertRedirect(route('attendance-sessions.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('attendance_sessions', [
        'id' => $session->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'attendance.session_deleted',
        'auditable_type' => $session->getMorphClass(),
        'auditable_id' => $session->id,
        'user_id' => $context['admin']->id,
    ]);
});

it('prevents staff users from removing attendance sessions', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['staff'])
        ->delete(route('attendance-sessions.destroy', $session));

    $response->assertForbidden();

    $this->assertDatabaseHas('attendance_sessions', [
        'id' => $session->id,
    ]);
});

it('prevents administrators from removing sessions that contain attendance records', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    StudentAttendance::create([
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $response = $this->actingAs($context['admin'])
        ->delete(route('attendance-sessions.destroy', $session));

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHasErrors('attendance_session');

    $this->assertDatabaseHas('attendance_sessions', [
        'id' => $session->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Student Attendance Recording
|--------------------------------------------------------------------------
*/

it('allows staff users to record attendance for active enrollments', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['staff'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                        'remarks' => 'Present for the lesson.',
                    ],
                ],
            ]
        );

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('student_attendances', [
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
        'remarks' => 'Present for the lesson.',
    ]);

    $attendance = StudentAttendance::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_enrollment_id', $context['enrollment']->id)
        ->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'attendance.record_created',
        'auditable_type' => $attendance->getMorphClass(),
        'auditable_id' => $attendance->id,
        'user_id' => $context['staff']->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'attendance.record_created')
        ->where('auditable_id', $attendance->id)
        ->firstOrFail();

    expect(json_encode($auditLog->properties))
        ->not->toContain('Present for the lesson.');
});

it('allows administrators to record attendance for multiple students in one session', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $secondStudent = makeAttendanceManagementEnrollment(
        $context['academicClass']
    );

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                    [
                        'student_enrollment_id' => $secondStudent['enrollment']->id,
                        'status' => StudentAttendance::STATUS_LATE,
                        'remarks' => 'Arrived late.',
                    ],
                ],
            ]
        );

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHas('success');

    $this->assertDatabaseCount('student_attendances', 2);

    $this->assertDatabaseHas('student_attendances', [
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $this->assertDatabaseHas('student_attendances', [
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $secondStudent['enrollment']->id,
        'status' => StudentAttendance::STATUS_LATE,
        'remarks' => 'Arrived late.',
    ]);
});

it('updates existing attendance records instead of creating duplicates', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $attendance = StudentAttendance::create([
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $response = $this->actingAs($context['staff'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_ABSENT,
                        'remarks' => 'Absence recorded.',
                    ],
                ],
            ]
        );

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHas('success');

    $this->assertDatabaseCount('student_attendances', 1);

    $this->assertDatabaseHas('student_attendances', [
        'id' => $attendance->id,
        'status' => StudentAttendance::STATUS_ABSENT,
        'remarks' => 'Absence recorded.',
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'attendance.record_updated')
        ->where('auditable_id', $attendance->id)
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($context['staff']->id)
        ->and($auditLog->properties['changes']['status']['old'])
        ->toBe(StudentAttendance::STATUS_PRESENT)
        ->and($auditLog->properties['changes']['status']['new'])
        ->toBe(StudentAttendance::STATUS_ABSENT);

    expect(json_encode($auditLog->properties))
        ->not->toContain('Absence recorded.');
});

it('rejects attendance for enrollments belonging to another academic class', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $anotherClass = AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $anotherEnrollment = makeAttendanceManagementEnrollment(
        $anotherClass
    );

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $anotherEnrollment['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects attendance for completed enrollments', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $context['enrollment']->update([
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects attendance for withdrawn enrollments', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $context['enrollment']->update([
        'status' => StudentEnrollment::STATUS_WITHDRAWN,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects attendance for inactive students', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $context['student']->update([
        'status' => Student::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects attendance for archived students', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $context['student']->delete();

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects invalid attendance statuses', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => 'unknown',
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors('attendances.0.status');

    $this->assertDatabaseCount('student_attendances', 0);

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'attendance.record_created',
    ]);
});

it('requires at least one attendance record when saving attendance', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [],
            ]
        );

    $response->assertSessionHasErrors('attendances');

    $this->assertDatabaseCount('student_attendances', 0);
});

it('rejects duplicate enrollment IDs in one attendance submission', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $response = $this->actingAs($context['admin'])
        ->put(
            route('attendance-sessions.student-attendances.save', $session),
            [
                'attendances' => [
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_PRESENT,
                    ],
                    [
                        'student_enrollment_id' => $context['enrollment']->id,
                        'status' => StudentAttendance::STATUS_ABSENT,
                    ],
                ],
            ]
        );

    $response->assertSessionHasErrors();

    $this->assertDatabaseCount('student_attendances', 0);
});

/*
|--------------------------------------------------------------------------
| Historical Attendance Records
|--------------------------------------------------------------------------
*/

it('preserves and displays attendance records for archived students', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    StudentAttendance::create([
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $studentNumber = $context['student']->student_number;

    $context['student']->delete();

    $response = $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.show', $session));

    $response->assertOk()
        ->assertSee('Historical Attendance Records')
        ->assertSee($studentNumber);

    $this->assertDatabaseHas('student_attendances', [
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);
});

/*
|--------------------------------------------------------------------------
| Individual Attendance Record Removal
|--------------------------------------------------------------------------
*/

it('allows administrators to remove individual attendance records', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $attendance = StudentAttendance::create([
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $response = $this->actingAs($context['admin'])
        ->delete(route('student-attendances.destroy', $attendance));

    $response->assertRedirect(
        route('attendance-sessions.show', $session)
    )->assertSessionHas('success');

    $this->assertDatabaseMissing('student_attendances', [
        'id' => $attendance->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'attendance.record_deleted',
        'auditable_type' => $attendance->getMorphClass(),
        'auditable_id' => $attendance->id,
        'user_id' => $context['admin']->id,
    ]);
});

it('prevents staff users from removing individual attendance records', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    $attendance = StudentAttendance::create([
        'attendance_session_id' => $session->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    $response = $this->actingAs($context['staff'])
        ->delete(route('student-attendances.destroy', $attendance));

    $response->assertForbidden();

    $this->assertDatabaseHas('student_attendances', [
        'id' => $attendance->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Attendance Filtering and Pagination Tests (Step 3)
|--------------------------------------------------------------------------
*/

it('filters attendance sessions by teacher class and subject', function (): void {
    $context = makeAttendanceManagementContext();

    $session = makeAttendanceManagementSession($context);

    foreach ([
        'teacher_id' => $context['teacher']->id,
        'academic_class_id' => $context['academicClass']->id,
        'subject_id' => $context['subject']->id,
    ] as $filter => $value) {
        $this->actingAs($context['admin'])
            ->get(route('attendance-sessions.index', [
                $filter => $value,
            ]))
            ->assertOk()
            ->assertViewHas('attendanceSessions', function ($sessions) use ($session): bool {
                return $sessions->contains('id', $session->id);
            });
    }
});

it('filters attendance sessions by inclusive date range', function (): void {
    $context = makeAttendanceManagementContext();

    $before = makeAttendanceManagementSession($context, '2026-01-09');
    $start = makeAttendanceManagementSession($context, '2026-01-10');
    $end = makeAttendanceManagementSession($context, '2026-01-20');
    $after = makeAttendanceManagementSession($context, '2026-01-21');

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'attendance_from' => '2026-01-10',
            'attendance_to' => '2026-01-20',
        ]))
        ->assertOk()
        ->assertViewHas('attendanceSessions', function ($sessions) use ($before, $start, $end, $after): bool {
            return ! $sessions->contains('id', $before->id)
                && $sessions->contains('id', $start->id)
                && $sessions->contains('id', $end->id)
                && ! $sessions->contains('id', $after->id);
        });
});

it('combines attendance relationship and date filters', function (): void {
    $context = makeAttendanceManagementContext();

    $matching = makeAttendanceManagementSession($context, '2026-02-15');
    $outsideRange = makeAttendanceManagementSession($context, '2026-03-15');

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'teacher_id' => $context['teacher']->id,
            'academic_class_id' => $context['academicClass']->id,
            'subject_id' => $context['subject']->id,
            'attendance_from' => '2026-02-01',
            'attendance_to' => '2026-02-28',
        ]))
        ->assertOk()
        ->assertViewHas('attendanceSessions', function ($sessions) use ($matching, $outsideRange): bool {
            return $sessions->contains('id', $matching->id)
                && ! $sessions->contains('id', $outsideRange->id);
        });
});

it('rejects invalid attendance filter values', function (): void {
    $context = makeAttendanceManagementContext();

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'teacher_id' => 999999,
        ]))
        ->assertSessionHasErrors('teacher_id');

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'subject_id' => 'invalid',
        ]))
        ->assertSessionHasErrors('subject_id');

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'attendance_from' => '2026-03-01',
            'attendance_to' => '2026-02-01',
        ]))
        ->assertSessionHasErrors('attendance_to');
});

it('preserves attendance filters during pagination', function (): void {
    $context = makeAttendanceManagementContext();

    for ($i = 0; $i < 11; $i++) {
        makeAttendanceManagementSession(
            $context,
            now()->subDays($i)->toDateString()
        );
    }

    $this->actingAs($context['admin'])
        ->get(route('attendance-sessions.index', [
            'teacher_id' => $context['teacher']->id,
            'subject_id' => $context['subject']->id,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertViewHas('attendanceSessions', function ($sessions) use ($context): bool {
            $url = $sessions->url(1);

            return $sessions->total() === 11
                && $sessions->currentPage() === 2
                && str_contains($url, 'teacher_id='.$context['teacher']->id)
                && str_contains($url, 'subject_id='.$context['subject']->id);
        });
});
