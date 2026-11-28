<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the timetable entries and locks tables', function (): void {
    expect(Schema::hasTable('timetable_entries'))->toBeTrue()
        ->and(Schema::hasTable('timetable_locks'))->toBeTrue();

    expect(Schema::hasColumns('timetable_entries', [
        'id',
        'academic_term_id',
        'teaching_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
        'created_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('initializes the shared timetable lock record', function (): void {
    expect(
        DB::table('timetable_locks')
            ->where('id', 1)
            ->exists()
    )->toBeTrue();
});

it('prevents duplicate timetable lock identifiers', function (): void {
    DB::table('timetable_locks')->insert(['id' => 2]);

    expect(
        DB::table('timetable_locks')->count()
    )->toBe(2);

    expect(fn () => DB::table('timetable_locks')->insert([
        'id' => 2,
    ]))->toThrow(QueryException::class);
});
