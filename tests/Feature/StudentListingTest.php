<?php

use App\Models\Student;

it('displays the student listing page', function () {
    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('Student Management')
        ->assertSee('No Students Found');
});

it('displays registered students in the listing', function () {
    Student::create([
        'student_number' => 'STU-TEST-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => 'alex@example.test',
        'phone' => '0771234567',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('STU-TEST-001')
        ->assertSee('Alex')
        ->assertSee('Johnson')
        ->assertSee('alex@example.test')
        ->assertSee('0771234567')
        ->assertSee('Active');
});

it('paginates the student listing', function () {
    foreach (range(1, 11) as $number) {
        Student::create([
            'student_number' => sprintf('STU-TEST-%03d', $number),
            'first_name' => 'Student',
            'last_name' => 'Test ' . $number,
            'enrollment_date' => '2026-01-15',
            'status' => 'active',
        ]);
    }

    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('Total registered students:')
        ->assertSee('11')
        ->assertSee('page=2', false);

    $secondPage = $this->get(route('students.index', ['page' => 2]));

    $secondPage->assertOk()
        ->assertSee('Total registered students:')
        ->assertSee('11');
});