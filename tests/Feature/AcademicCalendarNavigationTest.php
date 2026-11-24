<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows academic year navigation to active administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('academic-years.index'))
        ->assertOk()
        ->assertSee('Academic Years')
        ->assertSee(route('academic-years.index'), false);
});

it('shows academic year navigation to active staff', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('academic-years.index'))
        ->assertOk()
        ->assertSee('Academic Years');
});

it('redirects unauthenticated visitors away from academic years', function (): void {
    $this->actingAsGuest()
        ->get(route('academic-years.index'))
        ->assertRedirect(route('login'));
});

it('prevents staff from accessing the academic year creation page', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('academic-years.create'))
        ->assertForbidden();
});
