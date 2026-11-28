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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
- Create a valid timetable fixture using existing academic models.
 *
- @return array<string, mixed>
 */
function timetableFixture(): array
{
    $year = AcademicYear::factory()->create([
        'year' => 2035,
        'name' => 'Timetable Test 2035',
        'start_date' => '2035-01-01',
        'end_date' => '2035-12-31',
    ]);

    $term = AcademicTerm::factory()->create([
        'academic_year_id' => $year->id,
        'term_number' => 1,
        'start_date' => '2035-01-01',
        'end_date' => '2035-04-30',
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2035,
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

    return compact(
        'year',
        'term',
        'class',
        'teacher',
        'subject',
        'assignment',
        'admin'
    );
}

/**
- Build the attributes expected by the scheduling service.
 *
- @param  array<string, mixed>  $fixture
- @return array<string, mixed>
 */
function timetableAttributes(
    array $fixture,
    array $overrides = []
): array {
    return array_merge([
        'academic_term_id' => $fixture['term']->id,
        'teaching_assignment_id' => $fixture['assignment']->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '08:45',
    ], $overrides);
}

it('creates a valid timetable entry', function (): void {
    $fixture = timetableFixture();

    $entry = app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    expect($entry)->toBeInstanceOf(TimetableEntry::class)
        ->and($entry->academic_term_id)->toBe($fixture['term']->id)
        ->and($entry->teaching_assignment_id)
        ->toBe($fixture['assignment']->id)
        ->and($entry->day_of_week)->toBe(1);

    expect(TimetableEntry::count())->toBe(1);
});

it('rejects an overlapping period for the same class', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $otherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $otherAssignment = TeachingAssignment::factory()->create([
        'teacher_id' => $otherTeacher->id,
        'academic_class_id' => $fixture['class']->id,
        'subject_id' => $fixture['subject']->id,
    ]);

    expect(fn () => $service->create(
        timetableAttributes($fixture, [
            'teaching_assignment_id' => $otherAssignment->id,
            'start_time' => '08:30',
            'end_time' => '09:15',
        ]),
        $fixture['admin']
    ))->toThrow(ValidationException::class);

    expect(TimetableEntry::count())->toBe(1);
});

it('rejects an overlapping period for the same teacher', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $otherClass = AcademicClass::factory()->create([
        'academic_year' => 2035,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $otherClass->subjects()->attach($fixture['subject']->id);

    $otherAssignment = TeachingAssignment::factory()->create([
        'teacher_id' => $fixture['teacher']->id,
        'academic_class_id' => $otherClass->id,
        'subject_id' => $fixture['subject']->id,
    ]);

    expect(fn () => $service->create(
        timetableAttributes($fixture, [
            'teaching_assignment_id' => $otherAssignment->id,
            'start_time' => '08:30',
            'end_time' => '09:15',
        ]),
        $fixture['admin']
    ))->toThrow(ValidationException::class);

    expect(TimetableEntry::count())->toBe(1);
});

it('allows adjacent periods without an overlap', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $service->create(
        timetableAttributes($fixture, [
            'start_time' => '08:45',
            'end_time' => '09:30',
        ]),
        $fixture['admin']
    );

    expect(TimetableEntry::count())->toBe(2);
});

it('allows separate lessons on different weekdays', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $service->create(
        timetableAttributes($fixture, [
            'day_of_week' => 2,
        ]),
        $fixture['admin']
    );

    expect(TimetableEntry::count())->toBe(2);
});

it('rejects a timetable entry for a mismatched academic year', function (): void {
    $fixture = timetableFixture();

    $otherYear = AcademicYear::factory()->create([
        'year' => 2036,
        'name' => 'Timetable Test 2036',
        'start_date' => '2036-01-01',
        'end_date' => '2036-12-31',
    ]);

    $otherTerm = AcademicTerm::factory()->create([
        'academic_year_id' => $otherYear->id,
        'term_number' => 1,
        'start_date' => '2036-01-01',
        'end_date' => '2036-04-30',
    ]);

    expect(fn () => app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture, [
            'academic_term_id' => $otherTerm->id,
        ]),
        $fixture['admin']
    ))->toThrow(ValidationException::class);

    expect(TimetableEntry::count())->toBe(0);
});

it('rejects an invalid time range', function (): void {
    $fixture = timetableFixture();

    expect(fn () => app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture, [
            'start_time' => '09:00',
            'end_time' => '08:00',
        ]),
        $fixture['admin']
    ))->toThrow(ValidationException::class);

    expect(TimetableEntry::count())->toBe(0);
});

it('prevents staff from creating timetable entries', function (): void {
    $fixture = timetableFixture();

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    expect(fn () => app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $staff
    ))->toThrow(AuthorizationException::class);

    expect(TimetableEntry::count())->toBe(0);
});

it('prevents inactive administrators from scheduling lessons', function (): void {
    $fixture = timetableFixture();

    $fixture['admin']->update(['is_active' => false]);

    expect(fn () => app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']->fresh()
    ))->toThrow(AuthorizationException::class);

    expect(TimetableEntry::count())->toBe(0);
});

it('records a timetable creation audit event', function (): void {
    $fixture = timetableFixture();

    $entry = app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $auditCount = DB::table('audit_logs')
        ->where('event', 'timetable.entry.created')
        ->where('auditable_type', $entry->getMorphClass())
        ->where('auditable_id', $entry->id)
        ->count();

    expect($auditCount)->toBe(1);
});

it('updates an existing timetable entry', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $entry = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $updated = $service->update(
        $entry,
        timetableAttributes($fixture, [
            'start_time' => '09:00',
            'end_time' => '09:45',
        ]),
        $fixture['admin']
    );

    expect($updated->fresh()->start_time)->toBe('09:00:00')
        ->and($updated->fresh()->end_time)->toBe('09:45:00')
        ->and(TimetableEntry::count())->toBe(1);
});

it('rejects a timetable update that creates a conflict', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $first = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $second = $service->create(
        timetableAttributes($fixture, [
            'start_time' => '09:00',
            'end_time' => '09:45',
        ]),
        $fixture['admin']
    );

    expect(fn () => $service->update(
        $second,
        timetableAttributes($fixture, [
            'start_time' => '08:30',
            'end_time' => '09:15',
        ]),
        $fixture['admin']
    ))->toThrow(ValidationException::class);

    expect($first->fresh()->start_time)->toBe('08:00:00')
        ->and($second->fresh()->start_time)->toBe('09:00:00');
});

it('does not audit an unchanged timetable update', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $entry = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $service->update(
        $entry,
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $auditCount = DB::table('audit_logs')
        ->where('event', 'timetable.entry.updated')
        ->where('auditable_type', $entry->getMorphClass())
        ->where('auditable_id', $entry->id)
        ->count();

    expect($auditCount)->toBe(0);
});

it('deletes a timetable entry and records an audit event', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $entry = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $entryId = $entry->id;

    $service->delete($entry, $fixture['admin']);

    expect(TimetableEntry::find($entryId))->toBeNull();

    $auditCount = DB::table('audit_logs')
        ->where('event', 'timetable.entry.deleted')
        ->where('auditable_type', $entry->getMorphClass())
        ->where('auditable_id', $entryId)
        ->count();

    expect($auditCount)->toBe(1);
});

it('prevents staff from updating and deleting timetables', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $entry = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    expect(fn () => $service->update(
        $entry,
        timetableAttributes($fixture, [
            'start_time' => '10:00',
            'end_time' => '10:45',
        ]),
        $staff
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->delete(
        $entry,
        $staff
    ))->toThrow(AuthorizationException::class);

    expect(TimetableEntry::count())->toBe(1);
});

it('allows recreating a period after deletion', function (): void {
    $fixture = timetableFixture();

    $service = app(TimetableSchedulingService::class);

    $entry = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $service->delete($entry, $fixture['admin']);

    $replacement = $service->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    expect($replacement)->toBeInstanceOf(TimetableEntry::class)
        ->and(TimetableEntry::count())->toBe(1);
});

it('prevents changing a teaching assignment with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $otherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $response = $this
        ->actingAs($fixture['admin'])
        ->put(
            route(
                'teaching-assignments.update',
                $fixture['assignment']
            ),
            [
                'teacher_id' => $otherTeacher->id,
                'academic_class_id' => $fixture['class']->id,
                'subject_id' => $fixture['subject']->id,
            ]
        );

    $response->assertSessionHasErrors('teacher_id');

    expect(
        $fixture['assignment']->fresh()->teacher_id
    )->toBe($fixture['teacher']->id);
});

it('prevents deleting a teaching assignment with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $response = $this
        ->actingAs($fixture['admin'])
        ->delete(
            route(
                'teaching-assignments.destroy',
                $fixture['assignment']
            )
        );

    $response->assertSessionHasErrors(
        'teaching_assignment_id'
    );

    expect(
        TeachingAssignment::find(
            $fixture['assignment']->id
        )
    )->not->toBeNull();
});

it('allows an unchanged teaching assignment with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $response = $this
        ->actingAs($fixture['admin'])
        ->put(
            route(
                'teaching-assignments.update',
                $fixture['assignment']
            ),
            [
                'teacher_id' => $fixture['teacher']->id,
                'academic_class_id' => $fixture['class']->id,
                'subject_id' => $fixture['subject']->id,
            ]
        );

    $response->assertRedirect(
        route(
            'teaching-assignments.show',
            $fixture['assignment']
        )
    );

    expect(TimetableEntry::count())->toBe(1);

    expect(
        DB::table('audit_logs')
            ->where('event', 'teaching_assignment.updated')
            ->where(
                'auditable_id',
                $fixture['assignment']->id
            )
            ->count()
    )->toBe(0);
});

it('allows changing an unscheduled teaching assignment', function (): void {
    $fixture = timetableFixture();

    $otherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $response = $this
        ->actingAs($fixture['admin'])
        ->put(
            route(
                'teaching-assignments.update',
                $fixture['assignment']
            ),
            [
                'teacher_id' => $otherTeacher->id,
                'academic_class_id' => $fixture['class']->id,
                'subject_id' => $fixture['subject']->id,
            ]
        );

    $response->assertRedirect(
        route(
            'teaching-assignments.show',
            $fixture['assignment']
        )
    );

    expect(
        $fixture['assignment']->fresh()->teacher_id
    )->toBe($otherTeacher->id);
});

it('prevents deactivating an academic class with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $class = $fixture['class'];

    $attributes = $class->only([
        'name',
        'code',
        'grade_level',
        'academic_year',
        'description',
        'status',
    ]);

    $attributes['status'] = AcademicClass::STATUS_INACTIVE;

    $this->actingAs($fixture['admin'])
        ->put(route('academic-classes.update', $class), $attributes)
        ->assertSessionHasErrors('academic_year');

    expect($class->fresh()->status)
        ->toBe(AcademicClass::STATUS_ACTIVE);

    expect(TimetableEntry::count())->toBe(1);
});

it('prevents archiving an academic class with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $this->actingAs($fixture['admin'])
        ->delete(
            route('academic-classes.destroy', $fixture['class'])
        )
        ->assertSessionHasErrors('academic_class_id');

    expect($fixture['class']->fresh()->trashed())->toBeFalse();
});

it('prevents removing a scheduled subject from its class', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $this->actingAs($fixture['admin'])
        ->delete(
            route('academic-classes.subjects.destroy', [
                'academicClass' => $fixture['class'],
                'subject' => $fixture['subject'],
            ])
        )
        ->assertSessionHasErrors('subject_id');

    expect(
        $fixture['class']->subjects()
            ->whereKey($fixture['subject']->id)
            ->exists()
    )->toBeTrue();
});

it('prevents deactivating a teacher with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $teacher = $fixture['teacher'];

    $attributes = $teacher->only([
        'teacher_number',
        'first_name',
        'last_name',
        'email',
        'phone',
    ]);

    $attributes['date_of_birth'] =
        $teacher->date_of_birth?->format('Y-m-d');

    $attributes['employment_date'] =
        $teacher->employment_date->format('Y-m-d');

    $attributes['status'] = Teacher::STATUS_INACTIVE;

    $this->actingAs($fixture['admin'])
        ->put(route('teachers.update', $teacher), $attributes)
        ->assertSessionHasErrors('status');

    expect($teacher->fresh()->status)
        ->toBe(Teacher::STATUS_ACTIVE);

    expect(TimetableEntry::count())->toBe(1);
});

it('prevents archiving a teacher with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $this->actingAs($fixture['admin'])
        ->delete(route('teachers.destroy', $fixture['teacher']))
        ->assertSessionHasErrors('teacher_id');

    expect($fixture['teacher']->fresh()->trashed())
        ->toBeFalse();

    expect(TimetableEntry::count())->toBe(1);
});

it('prevents deactivating a subject with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $subject = $fixture['subject'];

    $attributes = $subject->only([
        'name',
        'code',
        'description',
        'status',
    ]);

    $attributes['status'] = Subject::STATUS_INACTIVE;

    $this->actingAs($fixture['admin'])
        ->put(
            route('subjects.update', $subject),
            $attributes
        )
        ->assertSessionHasErrors('status');

    expect($subject->fresh()->status)
        ->toBe(Subject::STATUS_ACTIVE);

    expect(TimetableEntry::count())->toBe(1);
});

it('prevents archiving a subject with scheduled lessons', function (): void {
    $fixture = timetableFixture();

    app(TimetableSchedulingService::class)->create(
        timetableAttributes($fixture),
        $fixture['admin']
    );

    $this->actingAs($fixture['admin'])
        ->delete(
            route('subjects.destroy', $fixture['subject'])
        )
        ->assertSessionHasErrors('subject_id');

    expect($fixture['subject']->fresh()->trashed())
        ->toBeFalse();

    expect(TimetableEntry::count())->toBe(1);
});
