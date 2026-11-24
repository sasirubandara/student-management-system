<?php

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AcademicCalendar\AcademicTermService;
use App\Services\AcademicCalendar\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditIntegrityYearData(): array
{
    return [
        'year' => 2027,
        'name' => 'Academic Year 2027/2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ];
}

function auditIntegrityTermData(): array
{
    return [
        'name' => 'Term 1',
        'term_number' => 1,
        'start_date' => '2027-09-01',
        'end_date' => '2027-12-20',
    ];
}

function auditIntegrityAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);
}

it('does not audit an unchanged academic year update', function (): void {
    $admin = auditIntegrityAdmin();

    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    $this->actingAs($admin)
        ->put(
            route('academic-years.update', $year),
            auditIntegrityYearData()
        )
        ->assertRedirect(route('academic-years.show', $year));

    expect(
        AuditLog::query()
            ->where('event', 'academic_year.updated')
            ->where('auditable_id', $year->id)
            ->where('auditable_type', $year->getMorphClass())
            ->count()
    )->toBe(0);
});

it('does not audit an unchanged academic term update', function (): void {
    $admin = auditIntegrityAdmin();

    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...auditIntegrityTermData(),
    ]);

    $this->actingAs($admin)
        ->put(
            route('academic-terms.update', $term),
            auditIntegrityTermData()
        )
        ->assertRedirect(route('academic-years.show', $year));

    expect(
        AuditLog::query()
            ->where('event', 'academic_term.updated')
            ->where('auditable_id', $term->id)
            ->where('auditable_type', $term->getMorphClass())
            ->count()
    )->toBe(0);
});

it('rolls back a year update when the audit callback fails', function (): void {
    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    $originalName = $year->name;

    expect(fn () => app(AcademicYearService::class)->update(
        $year,
        [
            ...auditIntegrityYearData(),
            'name' => 'Changed Academic Year',
        ],
        function (): void {
            throw new RuntimeException('Simulated audit failure.');
        }
    ))->toThrow(RuntimeException::class);

    expect($year->fresh()->name)->toBe($originalName);
});

it('rolls back a term update when the audit callback fails', function (): void {
    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        ...auditIntegrityTermData(),
    ]);

    expect(fn () => app(AcademicTermService::class)->update(
        $term,
        [
            ...auditIntegrityTermData(),
            'name' => 'Changed Term 1',
        ],
        function (): void {
            throw new RuntimeException('Simulated audit failure.');
        }
    ))->toThrow(RuntimeException::class);

    expect($term->fresh()->name)->toBe('Term 1');
});

it('rolls back term creation when the audit callback fails', function (): void {
    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    expect(fn () => app(AcademicTermService::class)->create(
        [
            'academic_year_id' => $year->id,
            ...auditIntegrityTermData(),
        ],
        function (): void {
            throw new RuntimeException('Simulated audit failure.');
        }
    ))->toThrow(RuntimeException::class);

    expect(
        AcademicTerm::query()
            ->where('academic_year_id', $year->id)
            ->count()
    )->toBe(0);
});

it('records only the changed year fields', function (): void {
    $admin = auditIntegrityAdmin();

    $year = AcademicYear::factory()->create(
        auditIntegrityYearData()
    );

    $this->actingAs($admin)
        ->put(
            route('academic-years.update', $year),
            [
                ...auditIntegrityYearData(),
                'name' => 'Revised Academic Year',
            ]
        )
        ->assertRedirect(route('academic-years.show', $year));

    $audit = AuditLog::query()
        ->where('event', 'academic_year.updated')
        ->where('auditable_type', $year->getMorphClass())
        ->where('auditable_id', $year->id)
        ->firstOrFail();

    expect($audit->properties['changed_fields'])->toBe(['name'])
        ->and($audit->user_id)->toBe($admin->id);
});
