<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows active administrators to view the timetable directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.index'))
        ->assertOk()
        ->assertSee('Academic Timetables');
});

it('allows active staff to view the timetable directory', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('timetables.index'))
        ->assertOk()
        ->assertSee('Academic Timetables');
});

it('redirects guests away from the timetable directory', function (): void {
    // Override the authenticated user created by tests/Pest.php.
    auth()->logout();

    $this->get(route('timetables.index'))
        ->assertRedirect(route('login'));
});

it('rejects invalid timetable filters', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.index', [
            'academic_term_id' => 'invalid',
        ]))
        ->assertSessionHasErrors('academic_term_id');
});
