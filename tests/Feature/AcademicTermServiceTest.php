<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\AcademicCalendar\AcademicTermService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createTermTestYear(
    int $year = 2027
): AcademicYear {
    return AcademicYear::factory()->create([
        'year' => $year,
        'name' => 'Academic Year '.$year,
        'start_date' => $year.'-01-01',
        'end_date' => $year.'-12-31',
    ]);
}

function termTestData(
    AcademicYear $year,
    int $number,
    string $start,
    string $end
): array {
    return [
        'academic_year_id' => $year->id,
        'name' => 'Term '.$number,
        'term_number' => $number,
        'start_date' => $start,
        'end_date' => $end,
    ];
}

it('creates a valid academic term', function (): void {
    $year = createTermTestYear();

    $term = app(AcademicTermService::class)->create(
        termTestData($year, 1, '2027-01-01', '2027-03-31')
    );

    expect($term->exists)->toBeTrue()
        ->and($term->term_number)->toBe(1)
        ->and($term->academicYear->id)->toBe($year->id);

    $this->assertDatabaseHas('academic_terms', [
        'id' => $term->id,
        'academic_year_id' => $year->id,
        'term_number' => 1,
    ]);
});

it('allows consecutive non-overlapping terms', function (): void {
    $year = createTermTestYear();
    $service = app(AcademicTermService::class);

    $service->create(
        termTestData($year, 1, '2027-01-01', '2027-03-31')
    );

    $service->create(
        termTestData($year, 2, '2027-04-01', '2027-06-30')
    );

    expect($year->terms()->count())->toBe(2);
});

it('rejects terms outside their academic year', function (): void {
    $year = createTermTestYear();

    expect(fn () => app(AcademicTermService::class)->create(
        termTestData($year, 1, '2026-12-01', '2027-03-31')
    ))->toThrow(ValidationException::class);
});

it('rejects overlapping terms', function (): void {
    $year = createTermTestYear();
    $service = app(AcademicTermService::class);

    $service->create(
        termTestData($year, 1, '2027-01-01', '2027-04-30')
    );

    expect(fn () => $service->create(
        termTestData($year, 2, '2027-04-01', '2027-06-30')
    ))->toThrow(ValidationException::class);

    expect($year->terms()->count())->toBe(1);
});

it('rejects duplicate term numbers in one academic year', function (): void {
    $year = createTermTestYear();
    $service = app(AcademicTermService::class);

    $service->create(
        termTestData($year, 1, '2027-01-01', '2027-03-31')
    );

    expect(fn () => $service->create(
        termTestData($year, 1, '2027-04-01', '2027-06-30')
    ))->toThrow(ValidationException::class);
});

it('allows the same term number in different academic years', function (): void {
    $firstYear = createTermTestYear(2027);
    $secondYear = createTermTestYear(2028);

    $service = app(AcademicTermService::class);

    $service->create(
        termTestData($firstYear, 1, '2027-01-01', '2027-03-31')
    );

    $service->create(
        termTestData($secondYear, 1, '2028-01-01', '2028-03-31')
    );

    expect(AcademicTerm::query()->count())->toBe(2);
});

it('rejects term updates that overlap another term', function (): void {
    $year = createTermTestYear();
    $service = app(AcademicTermService::class);

    $service->create(
        termTestData($year, 1, '2027-01-01', '2027-03-31')
    );

    $second = $service->create(
        termTestData($year, 2, '2027-04-01', '2027-06-30')
    );

    expect(fn () => $service->update($second, [
        'name' => 'Term 2',
        'term_number' => 2,
        'start_date' => '2027-03-01',
        'end_date' => '2027-06-30',
    ]))->toThrow(ValidationException::class);

    expect($second->fresh()->start_date->format('Y-m-d'))
        ->toBe('2027-04-01');
});

it('does not allow changing a term parent academic year', function (): void {
    $firstYear = createTermTestYear(2027);
    $secondYear = createTermTestYear(2028);

    $service = app(AcademicTermService::class);

    $term = $service->create(
        termTestData($firstYear, 1, '2027-01-01', '2027-03-31')
    );

    expect(fn () => $service->update($term, [
        'academic_year_id' => $secondYear->id,
        'name' => 'Transferred Term',
        'term_number' => 1,
        'start_date' => '2028-01-01',
        'end_date' => '2028-03-31',
    ]))->toThrow(ValidationException::class);

    expect($term->fresh()->academic_year_id)
        ->toBe($firstYear->id);
});
