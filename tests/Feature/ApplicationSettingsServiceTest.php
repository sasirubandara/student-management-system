<?php

use App\Models\ApplicationSetting;
use App\Services\ApplicationSettingsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

afterEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

it('initializes the application settings record', function (): void {
    $service = app(ApplicationSettingsService::class);

    $settings = $service->getRecord();

    expect($settings->id)
        ->toBe(ApplicationSetting::SETTINGS_ID);

    expect($settings->institution_name)
        ->toBe('Student Management System');

    expect($settings->current_academic_year)
        ->toBe(now()->year);

    $this->assertDatabaseCount('application_settings', 1);
});

it('does not create duplicate application settings records', function (): void {
    $service = app(ApplicationSettingsService::class);

    $first = $service->getRecord();
    $second = $service->getRecord();

    expect($first->id)->toBe($second->id);

    $this->assertDatabaseCount('application_settings', 1);
});

it('retrieves all supported application settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    $settings = $service->all();

    expect($settings)
        ->toHaveKey('institution_name', 'Student Management System')
        ->toHaveKey('current_academic_year', now()->year)
        ->toHaveKey('timezone', config('app.timezone', 'UTC'))
        ->toHaveKey('date_format', 'Y-m-d');

    expect($settings)
        ->not->toHaveKey('id')
        ->not->toHaveKey('created_at')
        ->not->toHaveKey('updated_at');
});

it('retrieves individual application settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    expect($service->get('institution_name'))
        ->toBe('Student Management System');

    expect($service->get('current_academic_year'))
        ->toBe(now()->year);
});

it('returns a fallback for unknown application settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    expect($service->get('unknown_setting', 'fallback'))
        ->toBe('fallback');

    expect($service->get('unknown_setting'))
        ->toBeNull();
});

it('caches application settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    $settings = $service->all();

    expect(Cache::has(ApplicationSettingsService::CACHE_KEY))
        ->toBeTrue();

    expect(Cache::get(ApplicationSettingsService::CACHE_KEY))
        ->toBe($settings);
});

it('uses cached application settings until the cache is cleared', function (): void {
    $service = app(ApplicationSettingsService::class);

    $service->all();

    ApplicationSetting::query()
        ->whereKey(ApplicationSetting::SETTINGS_ID)
        ->update([
            'institution_name' => 'Updated Academy',
        ]);

    expect($service->get('institution_name'))
        ->toBe('Student Management System');

    $service->clearCache();

    expect($service->get('institution_name'))
        ->toBe('Updated Academy');
});

it('updates application settings successfully', function (): void {
    $service = app(ApplicationSettingsService::class);

    $settings = $service->update([
        'institution_name' => 'Example Academy',
        'institution_email' => 'office@example.org',
        'institution_phone' => '0112345678',
        'institution_address' => 'Example Address',
        'current_academic_year' => 2027,
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ]);

    expect($settings->institution_name)
        ->toBe('Example Academy');

    expect($settings->current_academic_year)
        ->toBe(2027);

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

it('invalidates cached settings after an update', function (): void {
    $service = app(ApplicationSettingsService::class);

    expect($service->get('institution_name'))
        ->toBe('Student Management System');

    expect(Cache::has(ApplicationSettingsService::CACHE_KEY))
        ->toBeTrue();

    $service->update([
        'institution_name' => 'Updated Institution',
    ]);

    expect(Cache::has(ApplicationSettingsService::CACHE_KEY))
        ->toBeFalse();

    expect($service->get('institution_name'))
        ->toBe('Updated Institution');
});

it('ignores unsupported attributes when updating settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    $settings = $service->update([
        'id' => 999,
        'institution_name' => 'Example Academy',
        'unsupported_setting' => 'ignored',
    ]);

    expect($settings->id)
        ->toBe(ApplicationSetting::SETTINGS_ID);

    expect($settings->institution_name)
        ->toBe('Example Academy');

    $this->assertDatabaseCount('application_settings', 1);

    $this->assertDatabaseMissing('application_settings', [
        'id' => 999,
    ]);
});

it('supports partial application settings updates', function (): void {
    $service = app(ApplicationSettingsService::class);

    $service->update([
        'institution_name' => 'Example Academy',
        'timezone' => 'Asia/Colombo',
    ]);

    $service->update([
        'current_academic_year' => 2027,
    ]);

    $settings = $service->all();

    expect($settings)
        ->toHaveKey('institution_name', 'Example Academy')
        ->toHaveKey('timezone', 'Asia/Colombo')
        ->toHaveKey('current_academic_year', 2027);
});
