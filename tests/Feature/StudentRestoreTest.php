<?php

use App\Models\Student;
use App\Models\User;

it('displays archived students without displaying active students', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $archivedStudent = Student::create([
        'student_number' => 'STU-RESTORE-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $archivedStudent->delete();

    Student::create([
        'student_number' => 'STU-RESTORE-002',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'enrollment_date' => '2026-02-10',
        'status' => 'active',
    ]);

    $this->get(route('students.archived'))
        ->assertOk()
        ->assertSee('Archived Students')
        ->assertSee('STU-RESTORE-001')
        ->assertDontSee('STU-RESTORE-002')
        ->assertViewHas('students', function ($students) {
            return $students->total() === 1;
        });
});

it('restores an archived student successfully', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $student = Student::create([
        'student_number' => 'STU-RESTORE-003',
        'first_name' => 'Michael',
        'last_name' => 'Perera',
        'email' => 'michael@example.test',
        'enrollment_date' => '2026-03-01',
        'status' => 'active',
    ]);

    $student->delete();

    $studentId = $student->id;

    $response = $this->post(
        route('students.restore', $studentId)
    );

    $response->assertRedirect(
        route('students.show', $studentId)
    )->assertSessionHas(
        'success',
        'Student restored successfully.'
    );

    $this->assertDatabaseHas('students', [
        'id' => $studentId,
        'student_number' => 'STU-RESTORE-003',
        'deleted_at' => null,
    ]);

    $this->get(route('students.index'))
        ->assertOk()
        ->assertSee('STU-RESTORE-003');
});

it('does not restore a student who is not archived', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $student = Student::create([
        'student_number' => 'STU-RESTORE-004',
        'first_name' => 'David',
        'last_name' => 'Silva',
        'enrollment_date' => '2026-04-01',
        'status' => 'active',
    ]);

    $this->post(
        route('students.restore', $student)
    )->assertNotFound();

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'deleted_at' => null,
    ]);
});

it('returns a 404 when attempting to restore a nonexistent student', function () {
    $this->post(
        route('students.restore', 999999)
    )->assertNotFound();
});