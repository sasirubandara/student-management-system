<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Services\ApplicationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('preserves numeric academic year settings without calendar records', function (): void {
    $settingsService = app(ApplicationSettingsService::class);

    expect(AcademicYear::query()->count())->toBe(0);

    $settingsService->update([
        'current_academic_year' => 2027,
    ]);

    expect($settingsService->get('current_academic_year'))
        ->toBe(2027);
});

it('does not change existing academic class years when a calendar year is registered', function (): void {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2027,
    ]);

    AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    expect($academicClass->fresh()->academic_year)
        ->toBe(2027);
});
