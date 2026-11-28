<?php

use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows administrators to view timetable entry details', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create();

    $this->actingAs($admin)
        ->get(route('timetables.show', $entry))
        ->assertOk()
        ->assertSee('Timetable Entry Details');
});

it('allows staff to view timetable entry details', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create();

    $this->actingAs($staff)
        ->get(route('timetables.show', $entry))
        ->assertOk();
});

it('prevents staff from accessing timetable editing', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create();

    $this->actingAs($staff)
        ->get(route('timetables.edit', $entry))
        ->assertForbidden();
});

it('prevents staff from deleting timetable entries', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create();

    $this->actingAs($staff)
        ->delete(route('timetables.destroy', $entry))
        ->assertForbidden();

    expect(TimetableEntry::find($entry->id))
        ->not->toBeNull();
});

it('allows administrators to delete timetable entries', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create();

    $this->actingAs($admin)
        ->delete(route('timetables.destroy', $entry))
        ->assertRedirect(route('timetables.index'));

    expect(TimetableEntry::find($entry->id))
        ->toBeNull();
});
