<?php

use App\Models\Student;

it('displays the student registration page', function () {
    $this->get(route('students.create'))
        ->assertOk()
        ->assertViewIs('students.create')
        ->assertSee('Student Registration');
});


it('registers a student successfully', function () {
    $studentData = [
        'student_number' => 'STU-TEST-001',
        'first_name' => 'Alex',
        'last_name' => 'Perera',
        'email' => 'alex@example.test',
        'phone' => '+94112223344',
        'date_of_birth' => '2005-03-15',
        'enrollment_date' => '2026-01-15',
    ];

    $this->post(route('students.store'), $studentData)
        ->assertRedirect(route('students.create'))
        ->assertSessionHas(
            'success',
            'Student registered successfully.'
        );

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-TEST-001',
        'first_name' => 'Alex',
        'last_name' => 'Perera',
        'email' => 'alex@example.test',
    ]);

    $student = Student::query()
        ->where('student_number', 'STU-TEST-001')
        ->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'student.created',
        'auditable_type' => $student->getMorphClass(),
        'auditable_id' => $student->getKey(),
        'user_id' => auth()->id(),
    ]);
});


it('rejects duplicate student numbers', function () {
    $studentData = [
        'student_number' => 'STU-TEST-002',
        'first_name' => 'Alex',
        'last_name' => 'Perera',
        'enrollment_date' => '2026-01-15',
    ];

    $this->post(route('students.store'), $studentData)
        ->assertRedirect(route('students.create'));

    $this->post(route('students.store'), $studentData)
        ->assertSessionHasErrors('student_number');

    $this->assertDatabaseCount('students', 1);
});


it('requires mandatory student fields', function () {
    $this->from(route('students.create'))
        ->post(route('students.store'), [])
        ->assertRedirect(route('students.create'))
        ->assertSessionHasErrors([
            'student_number',
            'first_name',
            'last_name',
            'enrollment_date',
        ]);

    $this->assertDatabaseCount('students', 0);

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'student.created',
    ]);
});


it('allows optional student fields to be empty', function () {
    $studentData = [
        'student_number' => 'STU-TEST-003',
        'first_name' => 'Sam',
        'last_name' => 'Fernando',
        'enrollment_date' => '2026-01-15',
    ];

    $this->post(route('students.store'), $studentData)
        ->assertRedirect(route('students.create'));

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-TEST-003',
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
    ]);
});


it('rejects an invalid email address', function () {
    $studentData = [
        'student_number' => 'STU-TEST-004',
        'first_name' => 'Alex',
        'last_name' => 'Perera',
        'email' => 'not-an-email',
        'enrollment_date' => '2026-01-15',
    ];

    $this->from(route('students.create'))
        ->post(route('students.store'), $studentData)
        ->assertRedirect(route('students.create'))
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('students', 0);
});