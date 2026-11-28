<?php

use App\Models\AcademicClass;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows administrators to view the timetable creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.create'))
        ->assertOk()
        ->assertSee('Add Timetable Entry');
});

it('prevents staff from viewing the creation form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('timetables.create'))
        ->assertForbidden();
});

it('prevents staff from storing timetable entries', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->post(route('timetables.store'), [])
        ->assertForbidden();

    expect(TimetableEntry::count())->toBe(0);
});

it('requires all timetable creation fields', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('timetables.store'), [])
        ->assertSessionHasErrors([
            'academic_term_id',
            'teaching_assignment_id',
            'day_of_week',
            'start_time',
            'end_time',
        ]);
});

it('allows administrators to create valid timetable entries', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $year = AcademicYear::factory()->create([
        'year' => 2037,
        'name' => 'Timetable HTTP Test 2037',
        'start_date' => '2037-01-01',
        'end_date' => '2037-12-31',
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
        'start_date' => '2037-01-01',
        'end_date' => '2037-04-30',
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2037,
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

    $this->actingAs($admin)
        ->post(route('timetables.store'), [
            'academic_term_id' => $term->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
        ])
        ->assertRedirect(route('timetables.index'))
        ->assertSessionHas(
            'success',
            'Timetable entry created successfully.'
        );

    $this->assertDatabaseHas('timetable_entries', [
        'academic_term_id' => $term->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
        'created_by' => $admin->id,
    ]);
});

it('shows the creation link only to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.index'))
        ->assertSee(
            route('timetables.create')
        );

    $this->actingAs($staff)
        ->get(route('timetables.index'))
        ->assertDontSee(
            'Add Timetable Entry'
        );
});
