<?php

use App\Models\Student;
use App\Models\User;

it('archives a student successfully', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $student = Student::create([
        'student_number' => 'STU-ARCHIVE-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'email' => 'alex@example.test',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->delete(
        route('students.destroy', $student)
    );

    $response->assertRedirect(route('students.index'))
        ->assertSessionHas(
            'success',
            'Student archived successfully.'
        );

    $this->assertSoftDeleted('students', [
        'id' => $student->id,
    ]);
});

it('keeps archived students out of the listing and search', function () {
    $student = Student::create([
        'student_number' => 'STU-ARCHIVE-002',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'email' => 'sarah@example.test',
        'enrollment_date' => '2026-02-10',
        'status' => 'active',
    ]);

    $student->delete();

    $this->get(route('students.index'))
        ->assertOk()
        ->assertDontSee('STU-ARCHIVE-002')
        ->assertViewHas('students', function ($students) {
            return $students->total() === 0;
        });

    $this->get(route('students.index', [
        'search' => 'STU-ARCHIVE-002',
    ]))
        ->assertOk()
        ->assertSee('No Matching Students');
});

it('returns a 404 when opening an archived student profile', function () {
    $student = Student::create([
        'student_number' => 'STU-ARCHIVE-003',
        'first_name' => 'Michael',
        'last_name' => 'Perera',
        'enrollment_date' => '2026-03-01',
        'status' => 'active',
    ]);

    $student->delete();

    $this->get(route('students.show', [
        'student' => $student->id,
    ]))->assertNotFound();
});

it('does not archive a student that does not exist', function () {
    $this->delete(route('students.destroy', [
        'student' => 999999,
    ]))->assertNotFound();
});