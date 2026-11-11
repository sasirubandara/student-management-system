<?php

use App\Models\AuditLog;
use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Teaching Assignment Test Helpers
|--------------------------------------------------------------------------
*/

function makeTeachingAssignmentDependencies(
    array $teacherAttributes = [],
    array $classAttributes = [],
    array $subjectAttributes = []
): array {
    $teacher = Teacher::factory()->create(array_merge([
        'status' => Teacher::STATUS_ACTIVE,
    ], $teacherAttributes));

    $academicClass = AcademicClass::factory()->create(array_merge([
        'status' => AcademicClass::STATUS_ACTIVE,
    ], $classAttributes));

    $subject = Subject::factory()->create(array_merge([
        'status' => Subject::STATUS_ACTIVE,
    ], $subjectAttributes));

    $academicClass->subjects()
        ->syncWithoutDetaching([$subject->id]);

    return compact('teacher', 'academicClass', 'subject');
}

function makeTeachingAssignmentRecord(
    array $teacherAttributes = [],
    array $classAttributes = [],
    array $subjectAttributes = []
): array {
    $records = makeTeachingAssignmentDependencies(
        $teacherAttributes,
        $classAttributes,
        $subjectAttributes
    );

    $assignment = TeachingAssignment::create([
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);

    return array_merge($records, [
        'assignment' => $assignment,
    ]);
}

/*
|--------------------------------------------------------------------------
| Teaching Assignment Listing and Viewing
|--------------------------------------------------------------------------
*/

it('allows administrators to view the teaching assignment directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($admin)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertSee($records['teacher']->teacher_number)
        ->assertSee($records['academicClass']->name)
        ->assertSee($records['subject']->name);
});

it('allows staff users to view the teaching assignment directory', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertSee($records['teacher']->teacher_number)
        ->assertSee($records['subject']->name);
});

it('displays the teaching assignments navigation link to authorized users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertSee(route('teaching-assignments.index'), false);
});

it('displays the add assignment link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertSee(route('teaching-assignments.create'), false);
});

it('hides the add assignment link from staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertDontSee(route('teaching-assignments.create'), false);
});

it('allows administrators and staff to view teaching assignment details', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.show', $records['assignment']));

    $response->assertOk()
        ->assertSee($records['teacher']->teacher_number)
        ->assertSee($records['academicClass']->name)
        ->assertSee($records['subject']->name);
});

/*
|--------------------------------------------------------------------------
| Teaching Assignment Searching
|--------------------------------------------------------------------------
*/

it('searches teaching assignments by teacher number', function (): void {
    $records = makeTeachingAssignmentRecord([
        'teacher_number' => 'TCH-ASSIGN-SEARCH-001',
    ]);

    $response = $this->get(route('teaching-assignments.index', [
        'search' => 'TCH-ASSIGN-SEARCH-001',
    ]));

    $response->assertOk()
        ->assertSee('TCH-ASSIGN-SEARCH-001');
});

it('searches teaching assignments by academic class code', function (): void {
    $records = makeTeachingAssignmentRecord(
        [],
        ['code' => 'CLS-ASSIGN-SEARCH-001']
    );

    $response = $this->get(route('teaching-assignments.index', [
        'search' => 'CLS-ASSIGN-SEARCH-001',
    ]));

    $response->assertOk()
        ->assertSee('CLS-ASSIGN-SEARCH-001');
});

it('searches teaching assignments by subject code', function (): void {
    $records = makeTeachingAssignmentRecord(
        [],
        [],
        ['code' => 'SUB-ASSIGN-SEARCH-001']
    );

    $response = $this->get(route('teaching-assignments.index', [
        'search' => 'SUB-ASSIGN-SEARCH-001',
    ]));

    $response->assertOk()
        ->assertSee('SUB-ASSIGN-SEARCH-001');
});

/*
|--------------------------------------------------------------------------
| Teaching Assignment Creation
|--------------------------------------------------------------------------
*/

it('allows administrators to view the teaching assignment creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentDependencies();

    $response = $this->actingAs($admin)
        ->get(route('teaching-assignments.create'));

    $response->assertOk()
        ->assertSee($records['teacher']->teacher_number)
        ->assertSee($records['academicClass']->code)
        ->assertSee($records['subject']->code);
});

it('prevents staff users from viewing the teaching assignment creation form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.create'));

    $response->assertForbidden();
});

it('allows administrators to create teaching assignments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentDependencies();

    $response = $this->actingAs($admin)
        ->post(route('teaching-assignments.store'), [
            'teacher_id' => $records['teacher']->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]);

    $assignment = TeachingAssignment::query()
        ->where('teacher_id', $records['teacher']->id)
        ->where('academic_class_id', $records['academicClass']->id)
        ->where('subject_id', $records['subject']->id)
        ->firstOrFail();

    $response->assertRedirect(
        route('teaching-assignments.show', $assignment)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $assignment->id,
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'teaching_assignment.created')
        ->where('auditable_type', $assignment->getMorphClass())
        ->where('auditable_id', $assignment->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('prevents staff users from creating teaching assignments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentDependencies();

    $response = $this->actingAs($staff)
        ->post(route('teaching-assignments.store'), [
            'teacher_id' => $records['teacher']->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseCount('teaching_assignments', 0);
});

it('rejects duplicate teacher class and subject assignments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($admin)
        ->post(route('teaching-assignments.store'), [
            'teacher_id' => $records['teacher']->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]);

    $response->assertSessionHasErrors('teacher_id');

    $this->assertDatabaseCount('teaching_assignments', 1);

    expect(
        AuditLog::query()
            ->where('event', 'teaching_assignment.created')
            ->exists()
    )->toBeFalse();
});

it('rejects subjects that are not assigned to the selected academic class', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentDependencies();

    $unassignedSubject = Subject::factory()->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teaching-assignments.store'), [
            'teacher_id' => $records['teacher']->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $unassignedSubject->id,
        ]);

    $response->assertSessionHasErrors('subject_id');

    $this->assertDatabaseCount('teaching_assignments', 0);
});

it('rejects inactive teachers classes and subjects during assignment creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentDependencies(
        ['status' => Teacher::STATUS_INACTIVE],
        ['status' => AcademicClass::STATUS_INACTIVE],
        ['status' => Subject::STATUS_INACTIVE]
    );

    $response = $this->actingAs($admin)
        ->post(route('teaching-assignments.store'), [
            'teacher_id' => $records['teacher']->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]);

    $response->assertSessionHasErrors([
        'teacher_id',
        'academic_class_id',
        'subject_id',
    ]);

    $this->assertDatabaseCount('teaching_assignments', 0);
});

it('requires teacher class and subject when creating an assignment', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teaching-assignments.store'), []);

    $response->assertSessionHasErrors([
        'teacher_id',
        'academic_class_id',
        'subject_id',
    ]);
});

/*
|--------------------------------------------------------------------------
| Teaching Assignment Editing
|--------------------------------------------------------------------------
*/

it('allows administrators to view the teaching assignment editing form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($admin)
        ->get(route('teaching-assignments.edit', $records['assignment']));

    $response->assertOk()
        ->assertSee($records['teacher']->teacher_number)
        ->assertSee($records['academicClass']->code)
        ->assertSee($records['subject']->code);
});

it('prevents staff users from viewing the teaching assignment editing form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($staff)
        ->get(route('teaching-assignments.edit', $records['assignment']));

    $response->assertForbidden();
});

it('allows administrators to update teaching assignments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $anotherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->put(
            route('teaching-assignments.update', $records['assignment']),
            [
                'teacher_id' => $anotherTeacher->id,
                'academic_class_id' => $records['academicClass']->id,
                'subject_id' => $records['subject']->id,
            ]
        );

    $response->assertRedirect(
        route('teaching-assignments.show', $records['assignment'])
    )->assertSessionHas('success');

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $records['assignment']->id,
        'teacher_id' => $anotherTeacher->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'teaching_assignment.updated')
        ->where('auditable_type', $records['assignment']->getMorphClass())
        ->where('auditable_id', $records['assignment']->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('allows an assignment to retain its existing teacher class and subject during an update', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($admin)
        ->put(
            route('teaching-assignments.update', $records['assignment']),
            [
                'teacher_id' => $records['teacher']->id,
                'academic_class_id' => $records['academicClass']->id,
                'subject_id' => $records['subject']->id,
            ]
        );

    $response->assertRedirect(
        route('teaching-assignments.show', $records['assignment'])
    );

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $records['assignment']->id,
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);
});

it('rejects assigning a teacher already assigned to the same class and subject during updates', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentDependencies();

    $firstAssignment = TeachingAssignment::create([
        'teacher_id' => $records['teacher']->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);

    $anotherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    TeachingAssignment::create([
        'teacher_id' => $anotherTeacher->id,
        'academic_class_id' => $records['academicClass']->id,
        'subject_id' => $records['subject']->id,
    ]);

    $response = $this->actingAs($admin)
        ->put(route('teaching-assignments.update', $firstAssignment), [
            'teacher_id' => $anotherTeacher->id,
            'academic_class_id' => $records['academicClass']->id,
            'subject_id' => $records['subject']->id,
        ]);

    $response->assertSessionHasErrors('teacher_id');

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $firstAssignment->id,
        'teacher_id' => $records['teacher']->id,
    ]);
});

it('prevents staff users from updating teaching assignments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentRecord();

    $anotherTeacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($staff)
        ->put(
            route('teaching-assignments.update', $records['assignment']),
            [
                'teacher_id' => $anotherTeacher->id,
                'academic_class_id' => $records['academicClass']->id,
                'subject_id' => $records['subject']->id,
            ]
        );

    $response->assertForbidden();

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $records['assignment']->id,
        'teacher_id' => $records['teacher']->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Teaching Assignment Removal
|--------------------------------------------------------------------------
*/

it('allows administrators to remove teaching assignments', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($admin)
        ->delete(
            route('teaching-assignments.destroy', $records['assignment'])
        );

    $response->assertRedirect(route('teaching-assignments.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('teaching_assignments', [
        'id' => $records['assignment']->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'teaching_assignment.deleted')
        ->where(
            'auditable_type',
            $records['assignment']->getMorphClass()
        )
        ->where(
            'auditable_id',
            $records['assignment']->getKey()
        )
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('prevents staff users from removing teaching assignments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $records = makeTeachingAssignmentRecord();

    $response = $this->actingAs($staff)
        ->delete(
            route('teaching-assignments.destroy', $records['assignment'])
        );

    $response->assertForbidden();

    $this->assertDatabaseHas('teaching_assignments', [
        'id' => $records['assignment']->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Teaching Assignment Pagination
|--------------------------------------------------------------------------
*/

it('paginates the teaching assignment directory with ten assignments per page', function (): void {
    for ($i = 0; $i < 11; $i++) {
        makeTeachingAssignmentRecord();
    }

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('teaching-assignments.index'));

    $response->assertOk()
        ->assertViewHas('assignments', function ($assignments): bool {
            return $assignments->total() === 11
                && $assignments->perPage() === 10;
        });
});