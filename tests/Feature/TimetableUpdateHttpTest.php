<?php

use App\Models\AcademicClass;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\Timetables\TimetableSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Create a complete, valid timetable fixture.
 *
 * @return array<string, mixed>
 */
function timetableHttpFixture(): array
{
    $year = AcademicYear::factory()->create([
        'year' => 2045,
        'name' => 'Timetable HTTP Year 2045',
        'start_date' => '2045-01-01',
        'end_date' => '2045-12-31',
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
        'start_date' => '2045-01-01',
        'end_date' => '2045-04-30',
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2045,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $teacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $subject = Subject::factory()->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $class->subjects()->attach($subject->id);

    $assignment = TeachingAssignment::factory()->create([
        'teacher_id' => $teacher->id,
        'academic_class_id' => $class->id,
        'subject_id' => $subject->id,
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $attributes = [
        'academic_term_id' => $term->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '08:45',
    ];

    $entry = app(TimetableSchedulingService::class)
        ->create($attributes, $admin);

    return compact(
        'year',
        'term',
        'class',
        'teacher',
        'subject',
        'assignment',
        'admin',
        'attributes',
        'entry'
    );
}

it('allows administrators to view the timetable editing form', function (): void {
    $fixture = timetableHttpFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('timetables.edit', $fixture['entry']))
        ->assertOk()
        ->assertSee('Edit Timetable Entry')
        ->assertSee('Update Timetable Entry');
});

it('allows administrators to update a timetable entry', function (): void {
    $fixture = timetableHttpFixture();

    $this->actingAs($fixture['admin'])
        ->put(
            route('timetables.update', $fixture['entry']),
            array_merge($fixture['attributes'], [
                'start_time' => '09:00',
                'end_time' => '09:45',
            ])
        )
        ->assertRedirect(
            route('timetables.show', $fixture['entry'])
        )
        ->assertSessionHas('success');

    $this->assertDatabaseHas('timetable_entries', [
        'id' => $fixture['entry']->id,
        'start_time' => '09:00:00',
        'end_time' => '09:45:00',
        'created_by' => $fixture['admin']->id,
    ]);

    expect(
        DB::table('audit_logs')
            ->where('event', 'timetable.entry.updated')
            ->where('auditable_id', $fixture['entry']->id)
            ->count()
    )->toBe(1);
});

it('rejects invalid timetable update times', function (): void {
    $fixture = timetableHttpFixture();

    $this->actingAs($fixture['admin'])
        ->put(
            route('timetables.update', $fixture['entry']),
            array_merge($fixture['attributes'], [
                'start_time' => '10:00',
                'end_time' => '09:00',
            ])
        )
        ->assertSessionHasErrors('end_time');

    expect($fixture['entry']->fresh()->start_time)
        ->toBe('08:00:00');
});

it('rejects a conflicting timetable update', function (): void {
    $fixture = timetableHttpFixture();

    $second = app(TimetableSchedulingService::class)
        ->create(
            array_merge($fixture['attributes'], [
                'start_time' => '09:00',
                'end_time' => '09:45',
            ]),
            $fixture['admin']
        );

    $this->actingAs($fixture['admin'])
        ->put(
            route('timetables.update', $second),
            array_merge($fixture['attributes'], [
                'start_time' => '08:30',
                'end_time' => '09:15',
            ])
        )
        ->assertSessionHasErrors('start_time');

    expect($second->fresh()->start_time)
        ->toBe('09:00:00');

    expect(TimetableEntry::count())->toBe(2);
});

it('prevents staff from updating timetable entries', function (): void {
    $fixture = timetableHttpFixture();

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->put(
            route('timetables.update', $fixture['entry']),
            array_merge($fixture['attributes'], [
                'start_time' => '10:00',
                'end_time' => '10:45',
            ])
        )
        ->assertForbidden();

    expect($fixture['entry']->fresh()->start_time)
        ->toBe('08:00:00');
});

it('does not generate an audit event for unchanged updates', function (): void {
    $fixture = timetableHttpFixture();

    $this->actingAs($fixture['admin'])
        ->put(
            route('timetables.update', $fixture['entry']),
            $fixture['attributes']
        )
        ->assertRedirect(
            route('timetables.show', $fixture['entry'])
        );

    expect(
        DB::table('audit_logs')
            ->where('event', 'timetable.entry.updated')
            ->where('auditable_id', $fixture['entry']->id)
            ->count()
    )->toBe(0);
});

it('rejects missing required fields during timetable updates', function (): void {
    $fixture = timetableHttpFixture();

    $this->actingAs($fixture['admin'])
        ->put(
            route('timetables.update', $fixture['entry']),
            []
        )
        ->assertSessionHasErrors([
            'academic_term_id',
            'teaching_assignment_id',
            'day_of_week',
            'start_time',
            'end_time',
        ]);

    expect($fixture['entry']->fresh()->start_time)
        ->toBe('08:00:00');
});
