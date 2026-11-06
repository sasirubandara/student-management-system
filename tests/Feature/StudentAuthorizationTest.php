<?php

use App\Models\Student;
use App\Models\User;

it('prevents staff from viewing archived students', function () {
    $response = $this->get(route('students.archived'));

    $response->assertForbidden();
});

it('prevents staff from archiving students', function () {
    $student = Student::create([
        'student_number' => 'STU-AUTH-001',
        'first_name' => 'Alex',
        'last_name' => 'Johnson',
        'enrollment_date' => '2026-01-15',
        'status' => 'active',
    ]);

    $response = $this->delete(
        route('students.destroy', $student)
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'deleted_at' => null,
    ]);
});

it('prevents staff from restoring archived students', function () {
    $student = Student::create([
        'student_number' => 'STU-AUTH-002',
        'first_name' => 'Sarah',
        'last_name' => 'Fernando',
        'enrollment_date' => '2026-02-10',
        'status' => 'active',
    ]);

    $student->delete();

    $response = $this->post(
        route('students.restore', $student->id)
    );

    $response->assertForbidden();

    $this->assertSoftDeleted('students', [
        'id' => $student->id,
    ]);
});

it('allows administrators to view archived students', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('students.archived'));

    $response->assertOk()
        ->assertSee('Archived Students');
});

it('hides administrator navigation links from staff users', function () {
    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('Staff')
        ->assertDontSee(route('students.archived'), false);
});

it('shows administrator navigation links to administrators', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('Admin')
        ->assertSee(route('students.archived'), false);
});