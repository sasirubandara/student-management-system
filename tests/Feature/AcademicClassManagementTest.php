<?php

use App\Models\AuditLog;
use App\Models\AcademicClass;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

// Listing and viewing

it('allows administrators to view academic classes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create([
        'name' => 'Grade Ten Alpha',
        'code' => 'CLS-1001',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('academic-classes.index'));

    $response->assertOk()
        ->assertSee($academicClass->name)
        ->assertSee($academicClass->code);
});

it('allows staff users to view academic classes', function (): void {
    $academicClass = AcademicClass::factory()->create();

    $response = $this->get(route('academic-classes.index'));

    $response->assertOk()
        ->assertSee($academicClass->name);
});

it('allows authorized users to view class details', function (): void {
    $academicClass = AcademicClass::factory()->create([
        'name' => 'Grade Eleven Beta',
    ]);

    $response = $this->get(
        route('academic-classes.show', $academicClass)
    );

    $response->assertOk()
        ->assertSee($academicClass->name)
        ->assertSee($academicClass->code)
        ->assertSee((string) $academicClass->academic_year);
});

it('does not display soft-deleted classes in the listing', function (): void {
    $deletedClass = AcademicClass::factory()->create([
        'name' => 'Deleted Academic Class',
    ]);

    $activeClass = AcademicClass::factory()->create([
        'name' => 'Available Academic Class',
    ]);

    $deletedClass->delete();

    $response = $this->get(route('academic-classes.index'));

    $response->assertOk()
        ->assertSee($activeClass->name)
        ->assertDontSee($deletedClass->name);
});

// Class creation

it('allows administrators to view the class creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('academic-classes.create'));

    $response->assertOk()
        ->assertSee('Create Academic Class');
});

it('prevents staff users from viewing the class creation form', function (): void {
    $response = $this->get(route('academic-classes.create'));

    $response->assertForbidden();
});

it('allows administrators to create academic classes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.store'), [
            'name' => 'Grade Ten Gamma',
            'code' => 'CLS-2001',
            'grade_level' => 10,
            'academic_year' => 2026,
            'description' => 'Grade ten academic class.',
            'status' => AcademicClass::STATUS_ACTIVE,
        ]);

    $response->assertRedirect(route('academic-classes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('academic_classes', [
        'name' => 'Grade Ten Gamma',
        'code' => 'CLS-2001',
        'grade_level' => 10,
        'academic_year' => 2026,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $createdClass = AcademicClass::query()
        ->where('code', 'CLS-2001')
        ->firstOrFail();

    $auditLog = AuditLog::query()
        ->where('event', 'academic_class.created')
        ->where('auditable_type', $createdClass->getMorphClass())
        ->where('auditable_id', $createdClass->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('prevents staff users from creating academic classes', function (): void {
    $response = $this->post(route('academic-classes.store'), [
        'name' => 'Unauthorized Class',
        'code' => 'CLS-9999',
        'grade_level' => 10,
        'academic_year' => 2026,
        'description' => null,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('academic_classes', [
        'code' => 'CLS-9999',
    ]);
});

it('rejects duplicate academic class codes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    AcademicClass::factory()->create([
        'code' => 'CLS-DUP-01',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('academic-classes.store'), [
            'name' => 'Duplicate Class',
            'code' => 'CLS-DUP-01',
            'grade_level' => 8,
            'academic_year' => 2026,
            'description' => null,
            'status' => AcademicClass::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('code');

    expect(
        AuditLog::query()
            ->where('event', 'academic_class.created')
            ->exists()
    )->toBeFalse();
});

// Class editing

it('allows administrators to update academic classes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('academic-classes.update', $academicClass), [
            'name' => 'Updated Grade Ten',
            'code' => 'CLS-3001',
            'grade_level' => 10,
            'academic_year' => 2027,
            'description' => 'Updated class information.',
            'status' => AcademicClass::STATUS_INACTIVE,
        ]);

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('academic_classes', [
        'id' => $academicClass->id,
        'name' => 'Updated Grade Ten',
        'code' => 'CLS-3001',
        'academic_year' => 2027,
        'status' => AcademicClass::STATUS_INACTIVE,
    ]);

    $auditLog = AuditLog::query()
        ->where('event', 'academic_class.updated')
        ->where('auditable_type', $academicClass->getMorphClass())
        ->where('auditable_id', $academicClass->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());

    expect($auditLog->properties['changed_fields'])
        ->toContain('name', 'code', 'academic_year', 'status');
});

it('allows a class to retain its existing code when updating', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create([
        'code' => 'CLS-RETAIN-01',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('academic-classes.update', $academicClass), [
            'name' => 'Updated Class Name',
            'code' => 'CLS-RETAIN-01',
            'grade_level' => $academicClass->grade_level,
            'academic_year' => $academicClass->academic_year,
            'description' => $academicClass->description,
            'status' => $academicClass->status,
        ]);

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    );

    expect($academicClass->fresh()->name)
        ->toBe('Updated Class Name');
});

it('prevents staff users from viewing the class editing form', function (): void {
    $academicClass = AcademicClass::factory()->create();

    $response = $this->get(
        route('academic-classes.edit', $academicClass)
    );

    $response->assertForbidden();
});

it('prevents staff users from updating academic classes', function (): void {
    $academicClass = AcademicClass::factory()->create([
        'name' => 'Original Class Name',
    ]);

    $response = $this->put(
        route('academic-classes.update', $academicClass),
        [
            'name' => 'Unauthorized Updated Name',
            'code' => $academicClass->code,
            'grade_level' => $academicClass->grade_level,
            'academic_year' => $academicClass->academic_year,
            'description' => $academicClass->description,
            'status' => $academicClass->status,
        ]
    );

    $response->assertForbidden();

    expect($academicClass->fresh()->name)
        ->toBe('Original Class Name');
});

// Class deletion

it('allows administrators to soft-delete academic classes', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $academicClass = AcademicClass::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(
            route('academic-classes.destroy', $academicClass)
        );

    $response->assertRedirect(route('academic-classes.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($academicClass);

    $auditLog = AuditLog::query()
        ->where('event', 'academic_class.archived')
        ->where('auditable_type', $academicClass->getMorphClass())
        ->where('auditable_id', $academicClass->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('prevents staff users from deleting academic classes', function (): void {
    $academicClass = AcademicClass::factory()->create();

    $response = $this->delete(
        route('academic-classes.destroy', $academicClass)
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('academic_classes', [
        'id' => $academicClass->id,
        'deleted_at' => null,
    ]);
});

// Search and pagination

it('searches academic classes by name', function (): void {
    $matchingClass = AcademicClass::factory()->create([
        'name' => 'Unique Mathematics Class',
    ]);

    $otherClass = AcademicClass::factory()->create([
        'name' => 'Different Science Class',
    ]);

    $response = $this->get(route('academic-classes.index', [
        'search' => 'Unique Mathematics',
    ]));

    $response->assertOk()
        ->assertSee($matchingClass->name)
        ->assertDontSee($otherClass->name);
});

it('searches academic classes by class code', function (): void {
    $matchingClass = AcademicClass::factory()->create([
        'name' => 'Unique Search Class',
        'code' => 'SEARCH-2026',
    ]);

    $otherClass = AcademicClass::factory()->create([
        'name' => 'Another Class',
        'code' => 'OTHER-2026',
    ]);

    $response = $this->get(route('academic-classes.index', [
        'search' => 'SEARCH-2026',
    ]));

    $response->assertOk()
        ->assertSee($matchingClass->name)
        ->assertDontSee($otherClass->name);
});

it('paginates academic classes with ten records per page', function (): void {
    AcademicClass::factory()->count(11)->create();

    $response = $this->get(route('academic-classes.index'));

    $response->assertOk()
        ->assertViewHas(
            'academicClasses',
            function (LengthAwarePaginator $academicClasses): bool {
                return $academicClasses->perPage() === 10
                    && $academicClasses->total() === 11;
            }
        );
});