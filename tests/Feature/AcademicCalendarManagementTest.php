<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function calendarManagementYearData(): array
{
    return [
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ];
}

function calendarManagementTermData(): array
{
    return [
        'name' => 'Term 1',
        'term_number' => 1,
        'start_date' => '2027-09-01',
        'end_date' => '2027-12-20',
    ];
}

it('allows administrators to create academic years with audit logs', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isActive())->toBeTrue()
        ->and($admin->can('create', AcademicYear::class))
        ->toBeTrue();

    $data = calendarManagementYearData();

    expect($data)->toBe([
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('academic-years.store'), $data);

    $response->assertSessionHasNoErrors();

    $response->assertRedirect();

    $year = AcademicYear::query()
        ->where('year', 2027)
        ->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'academic_year.created',
        'user_id' => $admin->id,
        'auditable_id' => $year->id,
        'auditable_type' => $year->getMorphClass(),
    ]);
});

it('allows administrators to update academic years with audit logs', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create(calendarManagementYearData());

    $updated = calendarManagementYearData();
    $updated['name'] = 'Updated Academic Year';

    $this->actingAs($admin)
        ->put(route('academic-years.update', $year), $updated)
        ->assertRedirect(route('academic-years.show', $year));

    expect($year->fresh()->name)->toBe('Updated Academic Year');

    $audit = AuditLog::query()
        ->where('event', 'academic_year.updated')
        ->where('auditable_id', $year->id)
        ->firstOrFail();

    expect($audit->properties['changed_fields'])->toContain('name');
});

it('allows administrators to create terms with audit logs', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create(calendarManagementYearData());

    $this->actingAs($admin)
        ->post(
            route('academic-years.terms.store', $year),
            calendarManagementTermData()
        )
        ->assertRedirect(route('academic-years.show', $year));

    $term = AcademicTerm::query()->where('academic_year_id', $year->id)
        ->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'academic_term.created',
        'user_id' => $admin->id,
        'auditable_id' => $term->id,
        'auditable_type' => $term->getMorphClass(),
    ]);
});

it('allows administrators to update terms with audit logs', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create(calendarManagementYearData());

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...calendarManagementTermData(),
    ]);

    $updated = calendarManagementTermData();
    $updated['name'] = 'Updated Term 1';

    $this->actingAs($admin)
        ->put(route('academic-terms.update', $term), $updated)
        ->assertRedirect(route('academic-years.show', $year));

    expect($term->fresh()->name)->toBe('Updated Term 1');

    $audit = AuditLog::query()
        ->where('event', 'academic_term.updated')
        ->where('auditable_id', $term->id)
        ->firstOrFail();

    expect($audit->properties['changed_fields'])->toContain('name');
});

it('prevents staff from creating academic calendar records', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create(calendarManagementYearData());

    $this->actingAs($staff)
        ->post(route('academic-years.store'), [
            'year' => 2029,
            'name' => 'Academic Year 2029',
            'start_date' => '2029-01-01',
            'end_date' => '2029-12-31',
        ])
        ->assertForbidden();

    $this->post(
        route('academic-years.terms.store', $year),
        calendarManagementTermData()
    )->assertForbidden();

    expect(AcademicYear::query()->count())->toBe(1)
        ->and(AcademicTerm::query()->count())->toBe(0);
});

it('rejects academic years with overlapping dates through HTTP', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    AcademicYear::factory()->create(calendarManagementYearData());

    $this->actingAs($admin)
        ->from(route('academic-years.create'))
        ->post(route('academic-years.store'), [
            'year' => 2028,
            'name' => 'Overlapping Academic Year',
            'start_date' => '2028-01-01',
            'end_date' => '2028-12-31',
        ])
        ->assertRedirect(route('academic-years.create'))
        ->assertSessionHasErrors('start_date');

    expect(AcademicYear::query()->count())->toBe(1);
});

it('rolls back academic year creation when audit logging fails', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->mock(AuditLogger::class)
        ->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('Simulated audit failure.'));

    $this->actingAs($admin)->withoutExceptionHandling();

    expect(fn () => $this->post(
        route('academic-years.store'),
        calendarManagementYearData()
    ))->toThrow(RuntimeException::class);

    $this->assertDatabaseMissing('academic_years', [
        'year' => 2027,
    ]);
});
