<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

function calendarAuthorizationUser(
    string $role,
    bool $active = true
): User {
    return User::factory()->create([
        'role' => $role,
        'is_active' => $active,
    ]);
}

it('allows active administrators to view academic calendars', function (): void {
    $admin = calendarAuthorizationUser(User::ROLE_ADMIN);

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($admin)->allows('viewAny', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $year))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $term))
        ->toBeTrue();
});

it('allows active staff to view academic calendars', function (): void {
    $staff = calendarAuthorizationUser(User::ROLE_STAFF);

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($staff)->allows('viewAny', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->allows('view', $year))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->allows('viewAny', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->allows('view', $term))
        ->toBeTrue();
});

it('allows administrators to create and update academic calendars', function (): void {
    $admin = calendarAuthorizationUser(User::ROLE_ADMIN);

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($admin)->allows('create', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $year))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $term))
        ->toBeTrue();
});

it('prevents staff from modifying academic calendars', function (): void {
    $staff = calendarAuthorizationUser(User::ROLE_STAFF);

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($staff)->denies('create', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->denies('update', $year))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->denies('create', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($staff)->denies('update', $term))
        ->toBeTrue();
});

it('denies inactive administrators access to academic calendars', function (): void {
    $admin = calendarAuthorizationUser(
        User::ROLE_ADMIN,
        false
    );

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($admin)->denies('viewAny', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('view', $year))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('create', AcademicYear::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('update', $year))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('viewAny', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('view', $term))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('create', AcademicTerm::class))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('update', $term))
        ->toBeTrue();
});

it('prevents deletion of academic years and terms', function (): void {
    $admin = calendarAuthorizationUser(User::ROLE_ADMIN);

    $year = AcademicYear::factory()->create([
        'year' => 2027,
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(Gate::forUser($admin)->denies('delete', $year))
        ->toBeTrue()
        ->and(Gate::forUser($admin)->denies('delete', $term))
        ->toBeTrue();
});
