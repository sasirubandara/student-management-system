<?php

use App\Models\Student;

it('searches students by student number', function () {
    Student::create([
        'student_number' => 'STU-2026-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    Student::create([
        'student_number' => 'STU-2025-002',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'enrollment_date' => '2025-02-10',
        'status' => 'active',
    ]);

    $response = $this->get(route('students.index', [
        'search' => 'STU-2026',
    ]));

    $response->assertOk()
        ->assertSee('STU-2026-001')
        ->assertDontSee('STU-2025-002');
});

it('searches students by first name, last name and email', function () {
    Student::create([
        'student_number' => 'STU-TEST-001',
        'first_name' => 'Alicia',
        'last_name' => 'Fernando',
        'email' => 'alicia@example.test',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    // Search by first name.
    $this->get(route('students.index', [
        'search' => 'Alicia',
    ]))
        ->assertOk()
        ->assertSee('STU-TEST-001');

    // Search by last name.
    $this->get(route('students.index', [
        'search' => 'Fernando',
    ]))
        ->assertOk()
        ->assertSee('STU-TEST-001');

    // Search by email.
    $this->get(route('students.index', [
        'search' => 'alicia@example.test',
    ]))
        ->assertOk()
        ->assertSee('STU-TEST-001');
});

it('displays a message when no students match the search', function () {
    $response = $this->get(route('students.index', [
        'search' => 'NOTFOUND123',
    ]));

    $response->assertOk()
        ->assertSee('No Matching Students')
        ->assertSee('Try another student number, name or email.');
});

it('preserves the search query during pagination', function () {
    foreach (range(1, 11) as $number) {
        Student::create([
            'student_number' => sprintf('SEARCH-%03d', $number),
            'first_name' => 'Test',
            'last_name' => 'Student ' . $number,
            'enrollment_date' => '2026-01-15',
            'status' => 'active',
        ]);
    }

    $response = $this->get(route('students.index', [
        'search' => 'SEARCH',
        'page' => 2,
    ]));

    $response->assertOk()
        ->assertViewHas('students', function ($students) {
            return $students->total() === 11
                && $students->currentPage() === 2
                && str_contains($students->url(1), 'search=SEARCH');
        });
});