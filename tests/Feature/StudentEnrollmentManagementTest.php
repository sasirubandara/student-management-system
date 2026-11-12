<?php

use App\Models\AcademicClass;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;

// Enrollment listing and viewing

it('allows administrators to view the enrollment listing', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertSee($enrollment->student->student_number)
        ->assertSee($enrollment->academicClass->name);
});

it('allows staff users to view the enrollment listing', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertSee($enrollment->student->student_number);
});

it('displays the enrollment navigation link to authorized users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertSee(route('student-enrollments.index'), false);
});

it('displays the create enrollment link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertSee(route('student-enrollments.create'), false);
});

it('hides the create enrollment link from staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertDontSee(route('student-enrollments.create'), false);
});

it('allows authorized users to view enrollment details', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.show', $enrollment));

    $response->assertOk()
        ->assertSee($enrollment->student->student_number)
        ->assertSee($enrollment->academicClass->code);
});

it('preserves enrollment details when related records are archived', function (): void {
    $enrollment = StudentEnrollment::factory()->create();

    $studentNumber = $enrollment->student->student_number;
    $classCode = $enrollment->academicClass->code;

    $enrollment->student->delete();
    $enrollment->academicClass->delete();

    $response = $this->get(
        route('student-enrollments.show', $enrollment)
    );

    $response->assertOk()
        ->assertSee($studentNumber)
        ->assertSee($classCode);
});

// Enrollment creation

it('allows administrators to view the enrollment creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    Student::factory()->create();
    AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('student-enrollments.create'));

    $response->assertOk()
        ->assertSee('Create Student Enrollment');
});

it('prevents staff users from viewing the enrollment creation form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.create'));

    $response->assertForbidden();
});

it('allows administrators to create student enrollments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create();

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => '2026-01-15',
            'status' => StudentEnrollment::STATUS_ACTIVE,
            'notes' => 'Initial enrollment.',
        ]);

    $createdEnrollment = StudentEnrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_class_id', $academicClass->id)
        ->firstOrFail();

    $response->assertRedirect(
        route('student-enrollments.show', $createdEnrollment)
    );

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
        'notes' => 'Initial enrollment.',
    ]);

    expect($createdEnrollment->enrollment_date->toDateString())
        ->toBe('2026-01-15');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'enrollment.created',
        'auditable_type' => $createdEnrollment->getMorphClass(),
        'auditable_id' => $createdEnrollment->id,
        'user_id' => $admin->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'enrollment.created')
        ->where('auditable_id', $createdEnrollment->id)
        ->firstOrFail();

    expect($auditLog->properties['changed_fields'])
        ->toContain('student_id')
        ->toContain('academic_class_id')
        ->toContain('enrollment_date')
        ->toContain('status');

    expect(json_encode($auditLog->properties))
        ->not->toContain('Initial enrollment.');
});

it('notifies other active users when a student is enrolled', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $recipient = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $student = Student::factory()->create([
        'first_name' => 'Alex',
        'last_name' => 'Perera',
    ]);

    $academicClass = AcademicClass::factory()->create([
        'name' => 'Grade 10',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => '2026-01-15',
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $enrollment = StudentEnrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_class_id', $academicClass->id)
        ->firstOrFail();

    $response->assertRedirect(
        route('student-enrollments.show', $enrollment)
    );

    // Verify that another active user received the notification.
    $notification = $recipient->notifications()
        ->where('type', 'enrollment.created')
        ->firstOrFail();

    expect($notification->data)->toMatchArray([
        'event' => 'enrollment.created',
        'title' => 'New student enrollment',
        'message' => 'Alex Perera was enrolled in Grade 10.',
        'url' => route('student-enrollments.show', $enrollment),
        'icon' => 'graduation-cap',
    ]);

    // Verify that the acting administrator did not receive the notification.
    $this->assertDatabaseMissing('notifications', [
        'notifiable_type' => $admin->getMorphClass(),
        'notifiable_id' => $admin->getKey(),
        'type' => 'enrollment.created',
    ]);
});

it('prevents staff users from creating enrollments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $student = Student::factory()->create();

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($staff)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('student_enrollments', [
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
    ]);
});

it('rejects duplicate student and class enrollments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $enrollment->student_id,
            'academic_class_id' => $enrollment->academic_class_id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('student_id');

    $this->assertDatabaseCount('student_enrollments', 1);
});

it('rejects inactive students during enrollment creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create([
        'status' => 'inactive',
    ]);

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('student_id');
});

it('rejects archived students during enrollment creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create();

    $student->delete();

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('student_id');
});

it('rejects inactive academic classes during enrollment creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create();

    $academicClass = AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('academic_class_id');
});

it('rejects archived academic classes during enrollment creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create();

    $academicClass = AcademicClass::factory()->create();

    $academicClass->delete();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('academic_class_id');
});

it('rejects invalid enrollment statuses', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $student = Student::factory()->create();

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('student-enrollments.store'), [
            'student_id' => $student->id,
            'academic_class_id' => $academicClass->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

    $response->assertSessionHasErrors('status');

    $this->assertDatabaseMissing('student_enrollments', [
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
    ]);

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'enrollment.created',
    ]);

    $this->assertDatabaseCount('notifications', 0);
});

// Enrollment editing

it('allows administrators to view the enrollment editing form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('student-enrollments.edit', $enrollment));

    $response->assertOk()
        ->assertSee('Edit Student Enrollment');
});

it('allows administrators to update enrollment information', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('student-enrollments.update', $enrollment), [
            'enrollment_date' => '2026-02-20',
            'status' => StudentEnrollment::STATUS_COMPLETED,
            'notes' => 'Enrollment completed successfully.',
        ]);

    $response->assertRedirect(
        route('student-enrollments.show', $enrollment)
    );

    $response->assertSessionHas('success');
});
