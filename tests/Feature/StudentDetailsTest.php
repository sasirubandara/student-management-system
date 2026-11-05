<?php

use App\Models\Student;

it('displays the complete student profile', function () {
    $student = Student::create([
        'student_number' => 'STU-DETAIL-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => 'alex@example.test',
        'phone' => '0771234567',
        'date_of_birth' => '2005-06-15',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.show', $student));

    $response->assertOk()
        ->assertSee('Student Profile')
        ->assertSee('STU-DETAIL-001')
        ->assertSee('Alex')
        ->assertSee('Johnson')
        ->assertSee('alex@example.test')
        ->assertSee('0771234567')
        ->assertSee('15 June 2005')
        ->assertSee('15 January 2026')
        ->assertSee('Active');
});

it('displays a message when optional student information is missing', function () {
    $student = Student::create([
        'student_number' => 'STU-DETAIL-002',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
        'enrollment_date' => '2026-02-10',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.show', $student));

    $response->assertOk()
        ->assertSee('Not provided');
});

it('returns a 404 response for a student that does not exist', function () {
    $response = $this->get(route('students.show', [
        'student' => 999999,
    ]));

    $response->assertNotFound();
});

it('provides a profile link in the student listing', function () {
    $student = Student::create([
        'student_number' => 'STU-DETAIL-003',
        'first_name' => 'Michael',
        'last_name' => 'Perera',
        'enrollment_date' => '2026-03-01',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('View')
        ->assertSee(route('students.show', $student), false);
});