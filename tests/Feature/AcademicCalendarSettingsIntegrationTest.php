<?php

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows registered academic years in application settings', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('registered-academic-years')
        ->assertSee('Academic Year 2027/2028')
        ->assertSee('2027');
});

it('keeps application settings available when no academic years exist', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Current Academic Year')
        ->assertSee('No academic calendar records have been registered yet.');
});

it('prevents staff from modifying application settings', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('settings.edit'))
        ->assertForbidden();
});
