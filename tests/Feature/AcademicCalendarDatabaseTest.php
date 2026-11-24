<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an academic year with correct attributes', function (): void {
    $year = AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    expect($year->year)->toBe(2027)
        ->and($year->name)->toBe('Academic Year 2027/2028')
        ->and($year->start_date->format('Y-m-d'))->toBe('2027-09-01')
        ->and($year->end_date->format('Y-m-d'))->toBe('2028-06-30');

    $this->assertDatabaseHas('academic_years', [
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
    ]);
});

it('associates academic terms with their academic year', function (): void {
    $year = AcademicYear::factory()->create([
        'year' => 2027,
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'First Term',
        'term_number' => 1,
    ]);

    expect($year->terms()->count())->toBe(1)
        ->and($year->terms->first()->id)->toBe($term->id)
        ->and($term->academicYear->id)->toBe($year->id);
});

it('rejects duplicate academic year identifiers', function (): void {
    AcademicYear::factory()->create(['year' => 2027]);

    expect(fn () => AcademicYear::factory()->create([
        'year' => 2027,
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate term numbers within the same year', function (): void {
    $year = AcademicYear::factory()->create([
        'year' => 2027,
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
    ]);

    expect(fn () => AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
        'name' => 'Duplicate Term',
    ]))->toThrow(QueryException::class);
});

it('prevents deleting an academic year that has terms', function (): void {
    $year = AcademicYear::factory()->create([
        'year' => 2027,
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    expect(fn () => $year->delete())
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
    ]);
});
