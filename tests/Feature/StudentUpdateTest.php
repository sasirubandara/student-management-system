<?php

use App\Models\Student;

it('displays the student editing form with existing information', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => 'alex@example.test',
        'phone' => '0771234567',
        'date_of_birth' => '2005-06-15',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.edit', $student));

    $response->assertOk()
        ->assertSee('Edit Student')
        ->assertSee('STU-EDIT-001')
        ->assertSee('Alex')
        ->assertSee('Johnson')
        ->assertSee('alex@example.test');
});

it('updates student information successfully', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-002',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => 'alex@example.test',
        'phone' => '0771234567',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), [
        'student_number' => 'STU-EDIT-002',
        'first_name' => 'Alexander',
        'last_name' => 'Johnson',
        'email' => 'alexander@example.test',
        'phone' => '0779876543',
        'date_of_birth' => '2005-06-15',
        'enrollment_date' => '2026-02-01',
        'status' => 'inactive',
    ]);

    $response->assertRedirect(route('students.show', $student))
        ->assertSessionHas(
            'success',
            'Student information updated successfully.'
        );

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'student_number' => 'STU-EDIT-002',
        'first_name' => 'Alexander',
        'last_name' => 'Johnson',
        'email' => 'alexander@example.test',
        'phone' => '0779876543',
        'status' => 'inactive',
    ]);

    $auditLog = \App\Models\AuditLog::query()
        ->where('event', 'student.updated')
        ->where('auditable_id', $student->getKey())
        ->firstOrFail();

    expect($auditLog->auditable_type)
        ->toBe($student->getMorphClass());

    expect($auditLog->user_id)
        ->toBe(auth()->id());

    expect($auditLog->properties['changed_fields'])
        ->toContain('first_name')
        ->toContain('email')
        ->toContain('status');
});

it('allows a student to retain their existing student number', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-003',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), [
        'student_number' => 'STU-EDIT-003',
        'first_name' => 'Sarah',
        'last_name' => 'Perera',
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('students.show', $student))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'student_number' => 'STU-EDIT-003',
        'last_name' => 'Perera',
    ]);
});

it('rejects a student number already assigned to another student', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-004',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    Student::create([
        'student_number' => 'STU-EDIT-005',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), [
        'student_number' => 'STU-EDIT-005',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('student_number');

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'student_number' => 'STU-EDIT-004',
    ]);
});

it('rejects invalid email addresses when updating a student', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-006',
        'first_name' => 'Michael',
        'last_name' => 'Perera',
        'email' => 'michael@example.test',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), [
        'student_number' => 'STU-EDIT-006',
        'first_name' => 'Michael',
        'last_name' => 'Perera',
        'email' => 'invalid-email',
        'phone' => null,
        'date_of_birth' => null,
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('email');
});

it('requires mandatory fields when updating a student', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-007',
        'first_name' => 'David',
        'last_name' => 'Silva',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), []);

    $response->assertSessionHasErrors([
        'student_number',
        'first_name',
        'last_name',
        'enrollment_date',
        'status',
    ]);
});

it('does not create an audit record when student information is unchanged', function () {
    $student = Student::create([
        'student_number' => 'STU-EDIT-UNCHANGED',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'email' => 'sarah@example.test',
        'phone' => '0771234567',
        'date_of_birth' => '2005-06-15',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->put(route('students.update', $student), [
        'student_number' => 'STU-EDIT-UNCHANGED',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'email' => 'sarah@example.test',
        'phone' => '0771234567',
        'date_of_birth' => '2005-06-15',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('students.show', $student));

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'student.updated',
        'auditable_id' => $student->getKey(),
    ]);
});