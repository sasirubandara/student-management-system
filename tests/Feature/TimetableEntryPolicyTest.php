<?php

use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows active administrators to manage timetables', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $entry = new TimetableEntry;

    expect($admin->can('viewAny', TimetableEntry::class))->toBeTrue()
        ->and($admin->can('view', $entry))->toBeTrue()
        ->and($admin->can('create', TimetableEntry::class))->toBeTrue()
        ->and($admin->can('update', $entry))->toBeTrue()
        ->and($admin->can('delete', $entry))->toBeTrue();
});

it('allows staff to view but not modify timetables', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $entry = new TimetableEntry;

    expect($staff->can('viewAny', TimetableEntry::class))->toBeTrue()
        ->and($staff->can('view', $entry))->toBeTrue()
        ->and($staff->can('create', TimetableEntry::class))->toBeFalse()
        ->and($staff->can('update', $entry))->toBeFalse()
        ->and($staff->can('delete', $entry))->toBeFalse();
});

it('denies inactive administrators timetable permissions', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => false,
    ]);

    $entry = new TimetableEntry;

    expect($admin->can('viewAny', TimetableEntry::class))->toBeFalse()
        ->and($admin->can('view', $entry))->toBeFalse()
        ->and($admin->can('create', TimetableEntry::class))->toBeFalse()
        ->and($admin->can('update', $entry))->toBeFalse()
        ->and($admin->can('delete', $entry))->toBeFalse();
});
