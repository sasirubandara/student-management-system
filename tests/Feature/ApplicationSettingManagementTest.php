<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ApplicationSettingsService;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

afterEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

function makeApplicationSettingsAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);
}

function validApplicationSettingsPayload(): array
{
    return [
        'institution_name' => 'Example Academy',
        'institution_email' => 'office@example.org',
        'institution_phone' => '0112345678',
        'institution_address' => 'Example Address',
        'current_academic_year' => 2027,
        'timezone' => 'Asia/Colombo',
        'date_format' => 'd/m/Y',
    ];
}

it('allows administrators to view application settings', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Application Settings')
        ->assertSee('Institution Information')
        ->assertSee('Academic Preferences')
        ->assertSee('Localization Preferences')
        ->assertViewHas('settings');
});

it('prevents staff from viewing application settings', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('settings.edit'))
        ->assertForbidden();
});

it('requires authentication to view application settings', function (): void {
    $this->actingAsGuest();

    $this->get(route('settings.edit'))
        ->assertRedirect(route('login'));
});

it('allows administrators to update application settings', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $payload = validApplicationSettingsPayload();

    $this->actingAs($admin)
        ->patch(route('settings.update'), $payload)
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        ...$payload,
    ]);

    expect(
        app(ApplicationSettingsService::class)
            ->get('institution_name')
    )->toBe('Example Academy');
});

it('prevents staff from updating application settings', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->patch(
            route('settings.update'),
            validApplicationSettingsPayload()
        )
        ->assertForbidden();

    $this->assertDatabaseMissing('application_settings', [
        'institution_name' => 'Example Academy',
    ]);
});

it('requires authentication to update application settings', function (): void {
    $this->actingAsGuest();

    $this->patch(
        route('settings.update'),
        validApplicationSettingsPayload()
    )->assertRedirect(route('login'));
});

it('validates required application settings', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->from(route('settings.edit'))
        ->patch(route('settings.update'), [])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasErrors([
            'institution_name',
            'current_academic_year',
            'timezone',
            'date_format',
        ]);
});

it('rejects invalid application settings values', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $payload = [
        ...validApplicationSettingsPayload(),
        'institution_email' => 'invalid-email',
        'current_academic_year' => 1999,
        'timezone' => 'Invalid/Timezone',
        'date_format' => 'invalid-format',
    ];

    $this->actingAs($admin)
        ->from(route('settings.edit'))
        ->patch(route('settings.update'), $payload)
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasErrors([
            'institution_email',
            'current_academic_year',
            'timezone',
            'date_format',
        ]);
});

it('allows optional institution settings to be empty', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $payload = [
        ...validApplicationSettingsPayload(),
        'institution_email' => null,
        'institution_phone' => null,
        'institution_address' => null,
    ];

    $this->actingAs($admin)
        ->patch(route('settings.update'), $payload)
        ->assertRedirect(route('settings.edit'));

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_email' => null,
        'institution_phone' => null,
        'institution_address' => null,
    ]);
});

it('invalidates cached settings after an administrator updates them', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $service = app(ApplicationSettingsService::class);

    expect($service->get('institution_name'))
        ->toBe('Student Management System');

    $this->actingAs($admin)
        ->patch(
            route('settings.update'),
            validApplicationSettingsPayload()
        )
        ->assertRedirect(route('settings.edit'));

    expect($service->get('institution_name'))
        ->toBe('Example Academy');
});

it('ignores unsupported application settings attributes', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'id' => 999,
            'app_key' => 'unsupported-value',
        ])
        ->assertRedirect(route('settings.edit'));

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_name' => 'Example Academy',
    ]);

    $this->assertDatabaseMissing('application_settings', [
        'id' => 999,
    ]);

    expect(
        app(ApplicationSettingsService::class)->get('app_key')
    )->toBeNull();
});

it('displays the settings navigation link to administrators', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(
            'href="'.route('settings.edit').'"',
            false
        );
});

it('hides the settings navigation link from staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(
            'href="'.route('settings.edit').'"',
            false
        );
});

it('displays saved settings in the settings form', function (): void {
    $admin = makeApplicationSettingsAdmin();

    app(ApplicationSettingsService::class)->update(
        validApplicationSettingsPayload()
    );

    $this->actingAs($admin)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('value="Example Academy"', false)
        ->assertSee('value="office@example.org"', false)
        ->assertSee('value="0112345678"', false)
        ->assertSee('value="2027"', false)
        ->assertSee('Example Address')
        ->assertSee('Save Settings');
});

it('displays accessible validation errors on the settings form', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin);

    $settings = app(ApplicationSettingsService::class)->all();

    $this->withViewErrors([
        'institution_name' => 'The institution name field is required.',
        'institution_email' => 'The institution email must be a valid email address.',
    ])
        ->view('settings.edit', [
            'settings' => $settings,
            'timezones' => DateTimeZone::listIdentifiers(
                DateTimeZone::ALL
            ),
            'dateFormats' => ApplicationSetting::DATE_FORMATS,
        ])
        ->assertSee('Please review your settings.')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('id="institution-name-error"', false)
        ->assertSee('id="institution-email-error"', false)
        ->assertSee('aria-describedby="institution-name-error"', false)
        ->assertSee('aria-describedby="institution-email-error"', false)
        ->assertSee('The institution name field is required.')
        ->assertSee('The institution email must be a valid email address.');
});

it('records application settings changes in the audit log', function (): void {
    $admin = makeApplicationSettingsAdmin();

    app(ApplicationSettingsService::class)->getRecord();

    $this->actingAs($admin)
        ->patch(
            route('settings.update'),
            validApplicationSettingsPayload()
        )
        ->assertRedirect(route('settings.edit'));

    $log = AuditLog::query()
        ->where('event', 'application_settings.updated')
        ->where('user_id', $admin->id)
        ->firstOrFail();

    expect($log->auditable_id)
        ->toEqual(ApplicationSetting::SETTINGS_ID);

    expect($log->properties['changes']['institution_name'])
        ->toBe([
            'old' => 'Student Management System',
            'new' => 'Example Academy',
        ]);
});

it('does not create audit logs for unchanged application settings', function (): void {
    $admin = makeApplicationSettingsAdmin();

    app(ApplicationSettingsService::class)->update(
        validApplicationSettingsPayload()
    );

    $this->actingAs($admin)
        ->patch(
            route('settings.update'),
            validApplicationSettingsPayload()
        )
        ->assertRedirect(route('settings.edit'));

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'application_settings.updated',
        'user_id' => $admin->id,
    ]);
});

it('does not audit rejected application settings updates', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->from(route('settings.edit'))
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'current_academic_year' => 1999,
        ])
        ->assertSessionHasErrors('current_academic_year');

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'application_settings.updated',
        'user_id' => $admin->id,
    ]);
});

it('displays the configured institution name in navigation', function (): void {
    $admin = makeApplicationSettingsAdmin();

    app(ApplicationSettingsService::class)->update([
        'institution_name' => 'Example Academy',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Example Academy');
});

it('uses the configured academic year when creating academic classes', function (): void {
    $admin = makeApplicationSettingsAdmin();

    app(ApplicationSettingsService::class)->update([
        'current_academic_year' => 2030,
    ]);

    AcademicYear::factory()->create([
        'year' => 2030,
        'name' => 'Academic Year 2030',
        'start_date' => '2030-01-01',
        'end_date' => '2030-12-31',
    ]);

    $this->actingAs($admin)
        ->get(route('academic-classes.create'))
        ->assertOk()
        ->assertSee('value="2030"', false);
});

it('rolls back application settings when audit logging fails', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $service = app(ApplicationSettingsService::class);

    $service->getRecord();

    expect($service->get('institution_name'))
        ->toBe('Student Management System');

    $this->mock(AuditLogger::class)
        ->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('Audit logging failed.'));

    expect(fn () => $service->update([
        'institution_name' => 'Updated Academy',
        'current_academic_year' => 2030,
    ], $admin))->toThrow(
        RuntimeException::class,
        'Audit logging failed.'
    );

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_name' => 'Student Management System',
        'current_academic_year' => now()->year,
    ]);

    $this->assertDatabaseMissing('application_settings', [
        'institution_name' => 'Updated Academy',
    ]);

    expect($service->get('institution_name'))
        ->toBe('Student Management System');
});

it('records only changed application settings in audit properties', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $service = app(ApplicationSettingsService::class);

    $service->getRecord();

    $service->update([
        'institution_name' => 'Example Academy',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
    ], $admin);

    $log = AuditLog::query()
        ->where('event', 'application_settings.updated')
        ->where('user_id', $admin->id)
        ->firstOrFail();

    expect($log->properties['changes'])
        ->toBe([
            'institution_name' => [
                'old' => 'Student Management System',
                'new' => 'Example Academy',
            ],
        ]);
});

it('preserves unrelated cache entries when updating settings', function (): void {
    $service = app(ApplicationSettingsService::class);

    $service->all();

    Cache::put(
        'application_settings_test_unrelated',
        'preserved',
        60
    );

    $service->update([
        'institution_name' => 'Example Academy',
    ]);

    expect(Cache::get('application_settings_test_unrelated'))
        ->toBe('preserved');

    expect($service->get('institution_name'))
        ->toBe('Example Academy');

    Cache::forget('application_settings_test_unrelated');
});

// --- Step 3 — Authorization and Security Tests (Tests A–F) ---

it('denies application settings permissions to inactive administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => false,
    ]);

    expect($admin->can(
        'viewAny',
        ApplicationSetting::class
    ))->toBeFalse();

    expect($admin->can(
        'manage',
        ApplicationSetting::class
    ))->toBeFalse();
});

it('preserves existing settings after unauthorized update attempts', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $service = app(ApplicationSettingsService::class);

    $service->update([
        'institution_name' => 'Original Academy',
    ]);

    $original = $service->all();

    $this->actingAs($staff)
        ->patch(
            route('settings.update'),
            validApplicationSettingsPayload()
        )
        ->assertForbidden();

    expect($service->all())->toBe($original);

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_name' => 'Original Academy',
    ]);

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'application_settings.updated',
        'user_id' => $staff->id,
    ]);
});

it('preserves database and cached settings after invalid updates', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $service = app(ApplicationSettingsService::class);

    $original = $service->all();

    $this->actingAs($admin)
        ->from(route('settings.edit'))
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'institution_name' => 'Invalid Update',
            'current_academic_year' => 2101,
        ])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasErrors('current_academic_year');

    expect($service->all())->toBe($original);

    $this->assertDatabaseHas('application_settings', [
        'id' => ApplicationSetting::SETTINGS_ID,
        'institution_name' => $original['institution_name'],
        'current_academic_year' => $original['current_academic_year'],
    ]);

    $this->assertDatabaseMissing('application_settings', [
        'institution_name' => 'Invalid Update',
    ]);
});

it('escapes HTML in the configured institution name', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $institutionName = '<strong>Example Academy</strong>';

    app(ApplicationSettingsService::class)->update([
        'institution_name' => $institutionName,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($institutionName)
        ->assertDontSee($institutionName, false);
});

it('rejects oversized institution information', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $this->actingAs($admin)
        ->from(route('settings.edit'))
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'institution_name' => str_repeat('A', 256),
            'institution_phone' => str_repeat('1', 51),
            'institution_address' => str_repeat('A', 2001),
        ])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasErrors([
            'institution_name',
            'institution_phone',
            'institution_address',
        ]);
});

it('does not modify the application global timezone', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $originalTimezone = config('app.timezone');

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'timezone' => 'Asia/Colombo',
        ])
        ->assertRedirect(route('settings.edit'));

    expect(config('app.timezone'))
        ->toBe($originalTimezone);

    expect(
        app(ApplicationSettingsService::class)
            ->get('timezone')
    )->toBe('Asia/Colombo');
});

it('preserves existing academic class years when settings change', function (): void {
    $admin = makeApplicationSettingsAdmin();

    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2025,
    ]);

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...validApplicationSettingsPayload(),
            'current_academic_year' => 2030,
        ])
        ->assertRedirect(route('settings.edit'));

    expect($academicClass->fresh()->academic_year)
        ->toBe(2025);

    expect(
        app(ApplicationSettingsService::class)
            ->get('current_academic_year')
    )->toBe(2030);
});
