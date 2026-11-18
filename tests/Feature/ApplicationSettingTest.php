<?php

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Schema;

it('creates the application settings table', function (): void {
    expect(Schema::hasTable('application_settings'))
        ->toBeTrue();

    expect(Schema::hasColumns('application_settings', [
        'id',
        'institution_name',
        'institution_email',
        'institution_phone',
        'institution_address',
        'current_academic_year',
        'timezone',
        'date_format',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('provides default application settings', function (): void {
    $defaults = ApplicationSetting::defaults();

    expect($defaults)
        ->toHaveKey('institution_name', 'Student Management System')
        ->toHaveKey('institution_email', null)
        ->toHaveKey('institution_phone', null)
        ->toHaveKey('institution_address', null)
        ->toHaveKey('current_academic_year', now()->year)
        ->toHaveKey('timezone', config('app.timezone', 'UTC'))
        ->toHaveKey('date_format', 'Y-m-d');
});

it('persists application settings in the database', function (): void {
    $settings = new ApplicationSetting([
        'institution_name' => 'Example Academy',
        'institution_email' => 'office@example.org',
        'institution_phone' => '0112345678',
        'institution_address' => 'Example Address',
        'current_academic_year' => 2027,
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ]);

    $settings->id = ApplicationSetting::SETTINGS_ID;

    $settings->save();

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_name' => 'Example Academy',
        'institution_email' => 'office@example.org',
        'institution_phone' => '0112345678',
        'institution_address' => 'Example Address',
        'current_academic_year' => 2027,
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ]);
});

it('allows optional institution information to remain empty', function (): void {
    $settings = ApplicationSetting::create(
        ApplicationSetting::defaults()
    );

    expect($settings->fresh())
        ->institution_email->toBeNull()
        ->institution_phone->toBeNull()
        ->institution_address->toBeNull();
});

it('casts the current academic year to an integer', function (): void {
    $settings = ApplicationSetting::create([
        ...ApplicationSetting::defaults(),
        'current_academic_year' => '2027',
    ]);

    expect($settings->fresh()->current_academic_year)
        ->toBe(2027);
});
