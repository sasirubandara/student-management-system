<?php

use App\Models\Subject;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

// Subject listing and viewing

it('allows administrators to view the subject listing', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create([
        'name' => 'Mathematics',
        'code' => 'SUB-001',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('subjects.index'));

    $response->assertOk()
        ->assertSee($subject->name)
        ->assertSee($subject->code);
});

it('allows staff users to view the subject listing', function (): void {
    $subject = Subject::factory()->create();

    $response = $this->get(route('subjects.index'));

    $response->assertOk()
        ->assertSee($subject->name);
});

it('displays the create subject link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('subjects.index'));

    $response->assertOk()
        ->assertSee(route('subjects.create'), false);
});

it('hides the create subject link from staff users', function (): void {
    $response = $this->get(route('subjects.index'));

    $response->assertOk()
        ->assertDontSee(route('subjects.create'), false);
});

it('allows authorized users to view subject details', function (): void {
    $subject = Subject::factory()->create([
        'name' => 'Science',
        'code' => 'SUB-002',
    ]);

    $response = $this->get(route('subjects.show', $subject));

    $response->assertOk()
        ->assertSee($subject->name)
        ->assertSee($subject->code);
});

// Subject creation

it('allows administrators to view the subject creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('subjects.create'));

    $response->assertOk()
        ->assertSee('Create Subject');
});

it('prevents staff users from viewing the subject creation form', function (): void {
    $response = $this->get(route('subjects.create'));

    $response->assertForbidden();
});

it('allows administrators to create subjects', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('subjects.store'), [
            'name' => 'Mathematics',
            'code' => 'SUB-MATH-01',
            'description' => 'Mathematics subject.',
            'status' => Subject::STATUS_ACTIVE,
        ]);

    $response->assertRedirect(route('subjects.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('subjects', [
        'name' => 'Mathematics',
        'code' => 'SUB-MATH-01',
        'status' => Subject::STATUS_ACTIVE,
    ]);
});

it('prevents staff users from creating subjects', function (): void {
    $response = $this->post(route('subjects.store'), [
        'name' => 'Unauthorized Subject',
        'code' => 'SUB-UNAUTH-01',
        'description' => null,
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('subjects', [
        'code' => 'SUB-UNAUTH-01',
    ]);
});

it('rejects duplicate subject codes when creating subjects', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    Subject::factory()->create([
        'code' => 'SUB-DUP-01',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('subjects.store'), [
            'name' => 'Duplicate Subject',
            'code' => 'SUB-DUP-01',
            'description' => null,
            'status' => Subject::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('code');
});

it('rejects invalid subject statuses during creation', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('subjects.store'), [
            'name' => 'Invalid Status Subject',
            'code' => 'SUB-INVALID-01',
            'description' => null,
            'status' => 'pending',
        ]);

    $response->assertSessionHasErrors('status');

    $this->assertDatabaseMissing('subjects', [
        'code' => 'SUB-INVALID-01',
    ]);
});

// Subject editing

it('allows administrators to update subjects', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('subjects.update', $subject), [
            'name' => 'Updated Mathematics',
            'code' => 'SUB-MATH-UPDATED',
            'description' => 'Updated subject description.',
            'status' => Subject::STATUS_INACTIVE,
        ]);

    $response->assertRedirect(route('subjects.show', $subject))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'name' => 'Updated Mathematics',
        'code' => 'SUB-MATH-UPDATED',
        'status' => Subject::STATUS_INACTIVE,
    ]);
});

it('allows a subject to retain its existing code during an update', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create([
        'code' => 'SUB-RETAIN-01',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('subjects.update', $subject), [
            'name' => 'Updated Subject Name',
            'code' => 'SUB-RETAIN-01',
            'description' => $subject->description,
            'status' => $subject->status,
        ]);

    $response->assertRedirect(route('subjects.show', $subject));

    expect($subject->fresh()->name)
        ->toBe('Updated Subject Name');
});

it('rejects a subject code already assigned to another subject', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create();

    $anotherSubject = Subject::factory()->create([
        'code' => 'SUB-EXISTING-01',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('subjects.update', $subject), [
            'name' => 'Updated Subject',
            'code' => 'SUB-EXISTING-01',
            'description' => null,
            'status' => Subject::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('code');

    expect($subject->fresh()->code)
        ->not->toBe($anotherSubject->code);
});

it('allows administrators to view the subject editing form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('subjects.edit', $subject));

    $response->assertOk()
        ->assertSee('Edit Subject')
        ->assertSee($subject->name);
});

it('prevents staff users from viewing the subject editing form', function (): void {
    $subject = Subject::factory()->create();

    $response = $this->get(route('subjects.edit', $subject));

    $response->assertForbidden();
});

it('prevents staff users from updating subjects', function (): void {
    $subject = Subject::factory()->create([
        'name' => 'Original Subject',
    ]);

    $response = $this->put(
        route('subjects.update', $subject),
        [
            'name' => 'Unauthorized Updated Subject',
            'code' => $subject->code,
            'description' => $subject->description,
            'status' => $subject->status,
        ]
    );

    $response->assertForbidden();

    expect($subject->fresh()->name)
        ->toBe('Original Subject');
});

// Subject deletion

it('allows administrators to soft-delete subjects', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $subject = Subject::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('subjects.destroy', $subject));

    $response->assertRedirect(route('subjects.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($subject);
});

it('prevents staff users from deleting subjects', function (): void {
    $subject = Subject::factory()->create();

    $response = $this->delete(
        route('subjects.destroy', $subject)
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'deleted_at' => null,
    ]);
});

// Subject search and pagination

it('searches subjects by name', function (): void {
    $matchingSubject = Subject::factory()->create([
        'name' => 'Unique Mathematics Subject',
    ]);

    $otherSubject = Subject::factory()->create([
        'name' => 'Different Science Subject',
    ]);

    $response = $this->get(route('subjects.index', [
        'search' => 'Unique Mathematics',
    ]));

    $response->assertOk()
        ->assertSee($matchingSubject->name)
        ->assertDontSee($otherSubject->name);
});

it('searches subjects by subject code', function (): void {
    $matchingSubject = Subject::factory()->create([
        'name' => 'Unique Subject',
        'code' => 'SEARCH-SUB-01',
    ]);

    $otherSubject = Subject::factory()->create([
        'name' => 'Other Subject',
        'code' => 'OTHER-SUB-01',
    ]);

    $response = $this->get(route('subjects.index', [
        'search' => 'SEARCH-SUB-01',
    ]));

    $response->assertOk()
        ->assertSee($matchingSubject->name)
        ->assertDontSee($otherSubject->name);
});

it('paginates subjects with ten records per page', function (): void {
    Subject::factory()->count(11)->create();

    $response = $this->get(route('subjects.index'));

    $response->assertOk()
        ->assertViewHas(
            'subjects',
            function (LengthAwarePaginator $subjects): bool {
                return $subjects->perPage() === 10
                    && $subjects->total() === 11;
            }
        );
});