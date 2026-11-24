<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\AcademicCalendar\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function academicYearTestData(
    int $year,
    string $startDate,
    string $endDate
): array {
    return [
        'year' => $year,
        'name' => 'Academic Year '.$year,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ];
}

it('creates a valid academic year', function (): void {
    $service = app(AcademicYearService::class);

    $year = $service->create(
        academicYearTestData(
            2027,
            '2027-01-01',
            '2027-12-31'
        )
    );

    expect($year->exists)->toBeTrue()
        ->and($year->year)->toBe(2027);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'year' => 2027,
    ]);
});

it('supports academic years spanning two calendar years', function (): void {
    $service = app(AcademicYearService::class);

    $year = $service->create(
        academicYearTestData(
            2027,
            '2027-09-01',
            '2028-06-30'
        )
    );

    expect($year->start_date->format('Y-m-d'))
        ->toBe('2027-09-01')
        ->and($year->end_date->format('Y-m-d'))
        ->toBe('2028-06-30');
});

it('rejects an end date before the start date', function (): void {
    $service = app(AcademicYearService::class);

    expect(fn () => $service->create(
        academicYearTestData(
            2027,
            '2027-12-31',
            '2027-01-01'
        )
    ))->toThrow(ValidationException::class);
});

it('rejects overlapping academic years', function (): void {
    $service = app(AcademicYearService::class);

    $service->create(
        academicYearTestData(
            2027,
            '2027-01-01',
            '2027-12-31'
        )
    );

    expect(fn () => $service->create(
        academicYearTestData(
            2028,
            '2027-09-01',
            '2028-06-30'
        )
    ))->toThrow(ValidationException::class);

    expect(AcademicYear::query()->count())->toBe(1);
});

it('allows adjacent academic years without overlap', function (): void {
    $service = app(AcademicYearService::class);

    $service->create(
        academicYearTestData(
            2027,
            '2027-01-01',
            '2027-12-31'
        )
    );

    $nextYear = $service->create(
        academicYearTestData(
            2028,
            '2028-01-01',
            '2028-12-31'
        )
    );

    expect($nextYear->exists)->toBeTrue()
        ->and(AcademicYear::query()->count())->toBe(2);
});

it('rejects updates that overlap another academic year', function (): void {
    $service = app(AcademicYearService::class);

    $first = $service->create(
        academicYearTestData(
            2027,
            '2027-01-01',
            '2027-12-31'
        )
    );

    $second = $service->create(
        academicYearTestData(
            2028,
            '2028-01-01',
            '2028-12-31'
        )
    );

    expect(fn () => $service->update(
        $second,
        academicYearTestData(
            2028,
            '2027-06-01',
            '2028-12-31'
        )
    ))->toThrow(ValidationException::class);

    expect($first->fresh()->year)->toBe(2027)
        ->and($second->fresh()->start_date->format('Y-m-d'))
        ->toBe('2028-01-01');
});

it('prevents academic year updates that exclude existing terms', function (): void {
    $service = app(AcademicYearService::class);

    $year = $service->create(
        academicYearTestData(
            2027,
            '2027-01-01',
            '2027-12-31'
        )
    );

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'First Term',
        'term_number' => 1,
        'start_date' => '2027-01-01',
        'end_date' => '2027-04-30',
    ]);

    expect(fn () => $service->update(
        $year,
        academicYearTestData(
            2027,
            '2027-05-01',
            '2027-12-31'
        )
    ))->toThrow(ValidationException::class);

    expect($year->fresh()->start_date->format('Y-m-d'))
        ->toBe('2027-01-01');
});

it('rejects shortening an academic year when an existing term would fall outside it', function (): void {
    $year = AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'Term 1',
        'term_number' => 1,
        'start_date' => '2027-09-01',
        'end_date' => '2027-12-20',
    ]);

    expect(fn () => app(
        AcademicYearService::class
    )->update($year, [
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2028-01-01',
        'end_date' => '2028-06-30',
    ]))->toThrow(
        ValidationException::class
    );

    expect($year->fresh()->start_date->toDateString())
        ->toBe('2027-09-01');
});
