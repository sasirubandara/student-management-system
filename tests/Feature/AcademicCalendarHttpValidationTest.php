<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function calendarHttpYearData(): array
{
    return [
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ];
}

function calendarHttpTermData(): array
{
    return [
        'name' => 'Term 1',
        'term_number' => 1,
        'start_date' => '2027-09-01',
        'end_date' => '2027-12-20',
    ];
}

function calendarHttpAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);
}

it('redirects guests away from academic calendar write routes', function (): void {
    $year = AcademicYear::factory()->create(calendarHttpYearData());

    $this->actingAsGuest()
        ->post(route('academic-years.store'), calendarHttpYearData())
        ->assertRedirect(route('login'));

    $this->actingAsGuest()
        ->post(
            route('academic-years.terms.store', $year),
            calendarHttpTermData()
        )
        ->assertRedirect(route('login'));

    expect(AcademicYear::query()->count())->toBe(1)
        ->and(AcademicTerm::query()->count())->toBe(0);
});

it('rejects missing academic year fields', function (): void {
    $admin = calendarHttpAdmin();

    $this->actingAs($admin)
        ->post(route('academic-years.store'), [])
        ->assertSessionHasErrors([
            'year',
            'name',
            'start_date',
            'end_date',
        ]);

    expect(AcademicYear::query()->count())->toBe(0);
});

it('rejects duplicate academic year identifiers', function (): void {
    $admin = calendarHttpAdmin();

    AcademicYear::factory()->create(calendarHttpYearData());

    $duplicate = [
        'year' => 2027,
        'name' => 'Another Academic Year',
        'start_date' => '2029-01-01',
        'end_date' => '2029-12-31',
    ];

    $this->actingAs($admin)
        ->post(route('academic-years.store'), $duplicate)
        ->assertSessionHasErrors('year');

    expect(AcademicYear::query()->count())->toBe(1);
});

it('rejects a term outside its academic year', function (): void {
    $admin = calendarHttpAdmin();

    $year = AcademicYear::factory()->create(calendarHttpYearData());

    $invalidTerm = [
        ...calendarHttpTermData(),
        'start_date' => '2028-07-01',
        'end_date' => '2028-08-01',
    ];

    $this->actingAs($admin)
        ->post(
            route('academic-years.terms.store', $year),
            $invalidTerm
        )
        ->assertSessionHasErrors();

    expect(AcademicTerm::query()->count())->toBe(0);
});

it('rejects duplicate term numbers within the same year', function (): void {
    $admin = calendarHttpAdmin();

    $year = AcademicYear::factory()->create(calendarHttpYearData());

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...calendarHttpTermData(),
    ]);

    $duplicate = [
        'name' => 'Another Term 1',
        'term_number' => 1,
        'start_date' => '2028-01-01',
        'end_date' => '2028-03-31',
    ];

    $this->actingAs($admin)
        ->post(
            route('academic-years.terms.store', $year),
            $duplicate
        )
        ->assertSessionHasErrors('term_number');

    expect(AcademicTerm::query()->count())->toBe(1);
});

it('rejects overlapping academic terms', function (): void {
    $admin = calendarHttpAdmin();

    $year = AcademicYear::factory()->create(calendarHttpYearData());

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...calendarHttpTermData(),
    ]);

    $overlappingTerm = [
        'name' => 'Term 2',
        'term_number' => 2,
        'start_date' => '2027-12-20',
        'end_date' => '2028-03-31',
    ];

    $this->actingAs($admin)
        ->post(
            route('academic-years.terms.store', $year),
            $overlappingTerm
        )
        ->assertSessionHasErrors();

    expect(AcademicTerm::query()->count())->toBe(1);
});

it('prevents clients from assigning a term to another parent year', function (): void {
    $admin = calendarHttpAdmin();

    $year = AcademicYear::factory()->create(calendarHttpYearData());

    $submitted = [
        ...calendarHttpTermData(),
        'academic_year_id' => 999,
    ];

    $this->actingAs($admin)
        ->post(
            route('academic-years.terms.store', $year),
            $submitted
        )
        ->assertSessionHasErrors('academic_year_id');

    expect(AcademicTerm::query()->count())->toBe(0);
});

it('prevents staff from updating academic years and terms', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create(calendarHttpYearData());

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...calendarHttpTermData(),
    ]);

    $this->actingAs($staff)
        ->put(route('academic-years.update', $year), [
            ...calendarHttpYearData(),
            'name' => 'Unauthorized Change',
        ])
        ->assertForbidden();

    $this->actingAs($staff)
        ->put(route('academic-terms.update', $term), [
            ...calendarHttpTermData(),
            'name' => 'Unauthorized Term Change',
        ])
        ->assertForbidden();

    expect($year->fresh()->name)->toBe('Academic Year 2027/2028')
        ->and($term->fresh()->name)->toBe('Term 1');
});
