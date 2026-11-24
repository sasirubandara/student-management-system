<?php

use App\Models\AcademicYear;
use App\Services\AcademicCalendar\AcademicCalendarTransaction;
use App\Services\AcademicCalendar\AcademicTermService;
use App\Services\AcademicCalendar\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('creates the shared academic calendar lock record', function (): void {
    $this->assertDatabaseHas('academic_calendar_locks', [
        'id' => 1,
    ]);
});

it('executes an academic calendar operation in a transaction', function (): void {
    $result = app(AcademicCalendarTransaction::class)
        ->run(function (): int {
            expect(DB::transactionLevel())->toBeGreaterThan(0);

            return 42;
        });

    expect($result)->toBe(42);
});

it('rolls back academic calendar writes when an operation fails', function (): void {
    try {
        app(AcademicCalendarTransaction::class)
            ->run(function (): void {
                AcademicYear::query()->create([
                    'year' => 2027,
                    'name' => 'Academic Year 2027',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-12-31',
                ]);

                throw new RuntimeException(
                    'Simulated calendar operation failure.'
                );
            });

        $this->fail('Expected the transaction to fail.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toBe('Simulated calendar operation failure.');
    }

    $this->assertDatabaseMissing('academic_years', [
        'year' => 2027,
    ]);
});

it('fails closed when the shared lock record is missing', function (): void {
    DB::table('academic_calendar_locks')->delete();

    expect(fn () => app(AcademicYearService::class)->create([
        'year' => 2027,
        'name' => 'Academic Year 2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]))->toThrow(LogicException::class);

    $year = AcademicYear::factory()->create([
        'year' => 2028,
        'start_date' => '2028-01-01',
        'end_date' => '2028-12-31',
    ]);

    expect(fn () => app(AcademicTermService::class)->create([
        'academic_year_id' => $year->id,
        'name' => 'Term 1',
        'term_number' => 1,
        'start_date' => '2028-01-01',
        'end_date' => '2028-03-31',
    ]))->toThrow(LogicException::class);
});
