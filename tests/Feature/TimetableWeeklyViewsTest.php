<?php

use App\Models\AcademicClass;
use App\Models\AcademicTerm;
use App\Models\Teacher;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows administrators to view a class weekly timetable', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $class = AcademicClass::factory()->create();

    $this->actingAs($admin)
        ->get(route('timetables.classes.show', $class))
        ->assertOk()
        ->assertSee('Class Weekly Timetable')
        ->assertSee('Select an academic term');
});

it('allows staff to view a teacher weekly timetable', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $teacher = Teacher::factory()->create();

    $this->actingAs($staff)
        ->get(route('timetables.teachers.show', $teacher))
        ->assertOk()
        ->assertSee('Teacher Weekly Timetable');
});

it('displays scheduled lessons for the selected academic term', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create([
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    $class = $entry->teachingAssignment->academicClass;

    $term = $entry->academicTerm;

    // Align the class's academic year with the selected term.
    $class->update([
        'academic_year' => $term->academicYear->year,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.classes.show', [
            'academicClass' => $class,
            'academic_term_id' => $entry->academic_term_id,
        ]))
        ->assertOk()
        ->assertSee('Monday')
        ->assertSee('08:00')
        ->assertSee('08:45')
        ->assertSee(
            $entry->teachingAssignment->subject->name
        );
});

it('rejects invalid academic term filters', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $teacher = Teacher::factory()->create();

    $this->actingAs($admin)
        ->get(route('timetables.teachers.show', [
            'teacher' => $teacher,
            'academic_term_id' => 'invalid',
        ]))
        ->assertSessionHasErrors('academic_term_id');
});

it('redirects unauthenticated visitors from weekly timetables', function (): void {
    // Feature tests authenticate a user globally in tests/Pest.php.
    auth()->logout();

    $class = AcademicClass::factory()->create();

    $this->get(route('timetables.classes.show', $class))
        ->assertRedirect(route('login'));
});

it('does not display lessons from another academic term', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $entry = TimetableEntry::factory()->create([
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    $otherTerm = AcademicTerm::factory()->create();

    $teacher = $entry->teachingAssignment->teacher;

    $this->actingAs($admin)
        ->get(route('timetables.teachers.show', [
            'teacher' => $teacher,
            'academic_term_id' => $otherTerm->id,
        ]))
        ->assertOk()
        ->assertDontSee('08:00');
});

it('rejects an academic term from another year in a class timetable', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2026,
    ]);

    $otherTerm = AcademicTerm::factory()->create();

    // Ensure the selected term belongs to a different
    // academic year from the class.
    $otherTerm->academicYear()->update([
        'year' => 2027,
    ]);

    $this->actingAs($admin)
        ->get(route('timetables.classes.show', [
            'academicClass' => $class,
            'academic_term_id' => $otherTerm->id,
        ]))
        ->assertSessionHasErrors('academic_term_id');
});
