<?php

use App\Models\AcademicClass;
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

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $enrollment->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
        'notes' => 'Enrollment completed successfully.',
    ]);

    expect($enrollment->fresh()->enrollment_date->toDateString())
        ->toBe('2026-02-20');
});

it('does not allow enrollment associations to change during updates', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $anotherStudent = Student::factory()->create();

    $anotherClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('student-enrollments.update', $enrollment), [
            'student_id' => $anotherStudent->id,
            'academic_class_id' => $anotherClass->id,
            'enrollment_date' => '2026-03-10',
            'status' => StudentEnrollment::STATUS_ACTIVE,
            'notes' => null,
        ]);

    $response->assertRedirect(
        route('student-enrollments.show', $enrollment)
    );

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $enrollment->id,
        'student_id' => $enrollment->student_id,
        'academic_class_id' => $enrollment->academic_class_id,
    ]);
});

it('prevents staff users from viewing the enrollment editing form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('student-enrollments.edit', $enrollment));

    $response->assertForbidden();
});

it('prevents staff users from updating enrollments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($staff)
        ->put(route('student-enrollments.update', $enrollment), [
            'enrollment_date' => '2026-04-10',
            'status' => StudentEnrollment::STATUS_WITHDRAWN,
            'notes' => 'Unauthorized update.',
        ]);

    $response->assertForbidden();

    expect($enrollment->fresh()->status)
        ->toBe(StudentEnrollment::STATUS_ACTIVE);
});

// Enrollment searching and pagination

it('allows administrators to search enrollments by student number', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('student-enrollments.index', [
            'search' => $enrollment->student->student_number,
        ]));

    $response->assertOk()
        ->assertSee($enrollment->student->student_number);
});

it('allows users to search enrollments by academic class code', function (): void {
    $enrollment = StudentEnrollment::factory()->create();

    $response = $this->get(route('student-enrollments.index', [
        'search' => $enrollment->academicClass->code,
    ]));

    $response->assertOk()
        ->assertSee($enrollment->academicClass->code);
});

it('allows users to search enrollments by status', function (): void {
    $enrollment = StudentEnrollment::factory()->create([
        'status' => StudentEnrollment::STATUS_WITHDRAWN,
    ]);

    $response = $this->get(route('student-enrollments.index', [
        'search' => 'withdrawn',
    ]));

    $response->assertOk()
        ->assertSee($enrollment->student->student_number)
        ->assertSee('Withdrawn');
});

it('paginates enrollment listings into groups of ten', function (): void {
    StudentEnrollment::factory()->count(11)->create();

    $response = $this->get(route('student-enrollments.index'));

    $response->assertOk()
        ->assertViewHas('enrollments', function ($enrollments): bool {
            return $enrollments->total() === 11
                && $enrollments->perPage() === 10;
        });
});