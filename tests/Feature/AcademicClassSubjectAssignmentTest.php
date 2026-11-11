<?php

use App\Models\AuditLog;
use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\User;

// Viewing assigned subjects

it('allows administrators to view subjects assigned to an academic class', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subjects = Subject::factory()->count(2)->create();

    $academicClass->subjects()->attach($subjects->modelKeys());

    $response = $this->actingAs($admin)
        ->get(route('academic-classes.show', $academicClass));

    $response->assertOk()
        ->assertSee($subjects[0]->name)
        ->assertSee($subjects[0]->code)
        ->assertSee($subjects[1]->name)
        ->assertSee($subjects[1]->code);
});

it('allows staff users to view assigned subjects without assignment controls', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $academicClass->subjects()->attach($subject);

    $response = $this->actingAs($staff)
        ->get(route('academic-classes.show', $academicClass));

    $response->assertOk()
        ->assertSee($subject->name)
        ->assertDontSee(
            route('academic-classes.subjects.store', $academicClass),
            false
        );
});

it('displays the subject assignment form to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    Subject::factory()->create([
        'name' => 'Available Mathematics',
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('academic-classes.show', $academicClass));

    $response->assertOk()
        ->assertSee('Assign Subjects')
        ->assertSee(route('academic-classes.subjects.store', $academicClass), false)
        ->assertSee('Available Mathematics');
});

// Subject assignment

it('allows administrators to assign multiple subjects to an academic class', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subjects = Subject::factory()->count(3)->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => $subjects->modelKeys(),
        ]);

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    );

    $response->assertSessionHas('success');

    foreach ($subjects as $subject) {
        $this->assertDatabaseHas('academic_class_subject', [
            'academic_class_id' => $academicClass->id,
            'subject_id' => $subject->id,
        ]);
    }

    $this->assertDatabaseCount('academic_class_subject', 3);

    $auditLogs = AuditLog::query()
        ->where('event', 'academic_class.subject_assigned')
        ->where('auditable_type', $academicClass->getMorphClass())
        ->where('auditable_id', $academicClass->getKey())
        ->get();

    expect($auditLogs)->toHaveCount(3);

    $auditedSubjectIds = $auditLogs
        ->map(fn (AuditLog $auditLog) => $auditLog->properties['subject_id'])
        ->all();

    expect($auditedSubjectIds)
        ->toEqualCanonicalizing($subjects->modelKeys());

    foreach ($auditLogs as $auditLog) {
        expect($auditLog->user_id)->toBe($admin->getKey());
    }
});

it('allows a subject to be assigned to multiple academic classes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $firstClass = AcademicClass::factory()->create();

    $secondClass = AcademicClass::factory()->create();

    $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $firstClass), [
            'subject_ids' => [$subject->id],
        ])
        ->assertSessionHas('success');

    $this->post(route('academic-classes.subjects.store', $secondClass), [
        'subject_ids' => [$subject->id],
    ])
        ->assertSessionHas('success');

    $this->assertDatabaseCount('academic_class_subject', 2);
});

it('prevents duplicate subject assignments without creating duplicate records', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $academicClass->subjects()->attach($subject);

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [$subject->id],
        ]);

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    );

    $this->assertDatabaseCount('academic_class_subject', 1);
});

it('prevents staff users from assigning subjects to academic classes', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $response = $this->actingAs($staff)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [$subject->id],
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('academic_class_subject', [
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);
});

// Assignment validation

it('rejects inactive subjects during assignment', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create([
        'status' => Subject::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [$subject->id],
        ]);

    $response->assertSessionHasErrors('subject_ids.0');

    $this->assertDatabaseMissing('academic_class_subject', [
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);

    expect(
        AuditLog::query()
            ->where('event', 'academic_class.subject_assigned')
            ->where('auditable_id', $academicClass->getKey())
            ->exists()
    )->toBeFalse();
});

it('rejects archived subjects during assignment', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $subject->delete();

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [$subject->id],
        ]);

    $response->assertSessionHasErrors('subject_ids.0');

    $this->assertDatabaseMissing('academic_class_subject', [
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);
});

it('rejects duplicate subject IDs in a single assignment request', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [
                $subject->id,
                $subject->id,
            ],
        ]);

    $response->assertSessionHasErrors('subject_ids.1');

    $this->assertDatabaseCount('academic_class_subject', 0);
});

it('requires at least one subject during assignment', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.subjects.store', $academicClass), [
            'subject_ids' => [],
        ]);

    $response->assertSessionHasErrors('subject_ids');
});

// Removing subject assignments

it('allows administrators to remove a subject from an academic class', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $academicClass->subjects()->attach($subject);

    $response = $this->actingAs($admin)
        ->delete(route('academic-classes.subjects.destroy', [
            $academicClass,
            $subject,
        ]));

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    );

    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('academic_class_subject', [
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'academic_class.subject_removed')
        ->where('auditable_type', $academicClass->getMorphClass())
        ->where('auditable_id', $academicClass->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());

    expect($auditLog->properties['subject_id'])
        ->toBe($subject->getKey());
});

it('prevents staff users from removing subject assignments', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $academicClass->subjects()->attach($subject);

    $response = $this->actingAs($staff)
        ->delete(route('academic-classes.subjects.destroy', [
            $academicClass,
            $subject,
        ]));

    $response->assertForbidden();

    $this->assertDatabaseHas('academic_class_subject', [
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);
});

it('returns a 404 when removing a subject that is not assigned to the class', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('academic-classes.subjects.destroy', [
            $academicClass,
            $subject,
        ]));

    $response->assertNotFound();
});

// Relationship and availability checks

it('maintains the many-to-many relationships between classes and subjects', function (): void {
    $academicClass = AcademicClass::factory()->create();

    $subject = Subject::factory()->create();

    $academicClass->subjects()->attach($subject);

    expect($academicClass->fresh()->subjects->pluck('id')->all())
        ->toContain($subject->id);

    expect($subject->fresh()->academicClasses->pluck('id')->all())
        ->toContain($academicClass->id);
});

it('excludes inactive and already assigned subjects from the available subjects', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $assignedSubject = Subject::factory()->create();

    $availableSubject = Subject::factory()->create();

    $inactiveSubject = Subject::factory()->create([
        'status' => Subject::STATUS_INACTIVE,
    ]);

    $academicClass->subjects()->attach($assignedSubject);

    $response = $this->actingAs($admin)
        ->get(route('academic-classes.show', $academicClass));

    $response->assertOk()
        ->assertViewHas('availableSubjects', function ($availableSubjects) use (
            $assignedSubject,
            $availableSubject,
            $inactiveSubject
        ): bool {
            $availableIds = $availableSubjects->modelKeys();

            return in_array($availableSubject->id, $availableIds)
                && ! in_array($assignedSubject->id, $availableIds)
                && ! in_array($inactiveSubject->id, $availableIds);
        });
});