<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the timetable navigation link to active administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('timetables.index'));
});

it('shows the timetable navigation link to active staff', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('timetables.index'));
});

it('shows the timetable creation link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.index'))
        ->assertOk()
        ->assertSee('Add Timetable Entry');
});

it('hides the timetable creation link from staff', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('timetables.index'))
        ->assertOk()
        ->assertDontSee('Add Timetable Entry');
});
