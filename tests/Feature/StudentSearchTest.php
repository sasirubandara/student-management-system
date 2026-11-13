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
            'last_name' => 'Student '.$number,
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

it('filters students by status', function () {
    Student::factory()->create([
        'student_number' => 'FILTER-ACTIVE',
        'status' => Student::STATUS_ACTIVE,
    ]);

    Student::factory()->create([
        'student_number' => 'FILTER-INACTIVE',
        'status' => Student::STATUS_INACTIVE,
    ]);

    $this->get(route('students.index', [
        'status' => Student::STATUS_ACTIVE,
    ]))
        ->assertOk()
        ->assertSee('FILTER-ACTIVE')
        ->assertDontSee('FILTER-INACTIVE');
});

it('filters students by enrollment date range inclusively', function () {
    Student::factory()->create([
        'student_number' => 'DATE-BEFORE',
        'enrollment_date' => '2026-01-09',
    ]);

    Student::factory()->create([
        'student_number' => 'DATE-START',
        'enrollment_date' => '2026-01-10',
    ]);

    Student::factory()->create([
        'student_number' => 'DATE-END',
        'enrollment_date' => '2026-01-20',
    ]);

    Student::factory()->create([
        'student_number' => 'DATE-AFTER',
        'enrollment_date' => '2026-01-21',
    ]);

    $this->get(route('students.index', [
        'enrolled_from' => '2026-01-10',
        'enrolled_to' => '2026-01-20',
    ]))
        ->assertOk()
        ->assertSee('DATE-START')
        ->assertSee('DATE-END')
        ->assertDontSee('DATE-BEFORE')
        ->assertDontSee('DATE-AFTER');
});

it('combines search status and date filters', function () {
    Student::factory()->create([
        'student_number' => 'COMBINED-MATCH',
        'first_name' => 'UniqueFilterName',
        'status' => Student::STATUS_ACTIVE,
        'enrollment_date' => '2026-02-15',
    ]);

    Student::factory()->create([
        'student_number' => 'COMBINED-WRONG-STATUS',
        'first_name' => 'UniqueFilterName',
        'status' => Student::STATUS_INACTIVE,
        'enrollment_date' => '2026-02-15',
    ]);

    Student::factory()->create([
        'student_number' => 'COMBINED-WRONG-DATE',
        'first_name' => 'UniqueFilterName',
        'status' => Student::STATUS_ACTIVE,
        'enrollment_date' => '2026-03-15',
    ]);

    $this->get(route('students.index', [
        'search' => 'UniqueFilterName',
        'status' => Student::STATUS_ACTIVE,
        'enrolled_from' => '2026-02-01',
        'enrolled_to' => '2026-02-28',
    ]))
        ->assertOk()
        ->assertSee('COMBINED-MATCH')
        ->assertDontSee('COMBINED-WRONG-STATUS')
        ->assertDontSee('COMBINED-WRONG-DATE');
});

it('rejects invalid student filter values', function () {
    $this->get(route('students.index', [
        'status' => 'unknown',
    ]))->assertSessionHasErrors('status');

    $this->get(route('students.index', [
        'enrolled_from' => '2026-03-01',
        'enrolled_to' => '2026-02-01',
    ]))->assertSessionHasErrors('enrolled_to');
});

it('preserves advanced filters during pagination', function () {
    foreach (range(1, 11) as $number) {
        Student::factory()->create([
            'student_number' => sprintf('PAGED-%03d', $number),
            'status' => Student::STATUS_ACTIVE,
            'enrollment_date' => '2026-04-15',
        ]);
    }

    $this->get(route('students.index', [
        'status' => Student::STATUS_ACTIVE,
        'enrolled_from' => '2026-04-01',
        'page' => 2,
    ]))
        ->assertOk()
        ->assertViewHas('students', function ($students) {
            $url = $students->url(1);

            return $students->total() === 11
                && $students->currentPage() === 2
                && str_contains($url, 'status=active')
                && str_contains($url, 'enrolled_from=2026-04-01');
        });
});
