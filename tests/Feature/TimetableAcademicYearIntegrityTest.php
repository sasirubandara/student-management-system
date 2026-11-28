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

uses(RefreshDatabase::class);

function makeTimetableAcademicYearFixture(): array
{
    $year = AcademicYear::factory()->create([
        'year' => 2040,
        'name' => 'Academic Year 2040',
        'start_date' => '2040-01-01',
        'end_date' => '2040-12-31',
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
        'start_date' => '2040-01-01',
        'end_date' => '2040-04-30',
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2040,
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

    return compact('year', 'term', 'class', 'assignment', 'admin');
}

function academicYearUpdatePayload(
    AcademicYear $year,
    array $overrides = []
): array {
    return array_merge([
        'year' => $year->year,
        'name' => $year->name,
        'start_date' => $year->start_date->format('Y-m-d'),
        'end_date' => $year->end_date->format('Y-m-d'),
    ], $overrides);
}

it('prevents renumbering an academic year with scheduled lessons', function (): void {
    $fixture = makeTimetableAcademicYearFixture();

    app(TimetableSchedulingService::class)->create([
        'academic_term_id' => $fixture['term']->id,
        'teaching_assignment_id' => $fixture['assignment']->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '08:45',
    ], $fixture['admin']);

    $this->actingAs($fixture['admin'])
        ->put(
            route('academic-years.update', $fixture['year']),
            academicYearUpdatePayload($fixture['year'], [
                'year' => 2041,
            ])
        )
        ->assertSessionHasErrors('year');

    expect($fixture['year']->fresh()->year)->toBe(2040)
        ->and(TimetableEntry::count())->toBe(1);
});

it('prevents renumbering a year referenced by an academic class', function (): void {
    $fixture = makeTimetableAcademicYearFixture();

    $this->actingAs($fixture['admin'])
        ->put(
            route('academic-years.update', $fixture['year']),
            academicYearUpdatePayload($fixture['year'], [
                'year' => 2041,
            ])
        )
        ->assertSessionHasErrors('year');

    expect($fixture['year']->fresh()->year)->toBe(2040);
});

it('allows changing the name of an academic year with scheduled lessons', function (): void {
    $fixture = makeTimetableAcademicYearFixture();

    app(TimetableSchedulingService::class)->create([
        'academic_term_id' => $fixture['term']->id,
        'teaching_assignment_id' => $fixture['assignment']->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '08:45',
    ], $fixture['admin']);

    $this->actingAs($fixture['admin'])
        ->put(
            route('academic-years.update', $fixture['year']),
            academicYearUpdatePayload($fixture['year'], [
                'name' => 'Updated Academic Year 2040',
            ])
        )
        ->assertRedirect(
            route('academic-years.show', $fixture['year'])
        );

    expect($fixture['year']->fresh()->name)
        ->toBe('Updated Academic Year 2040');
});
