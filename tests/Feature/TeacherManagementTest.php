<?php

use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;

// Teacher listing and viewing

it('allows administrators to view the teacher directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create([
        'teacher_number' => 'TCH-TEST-001',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertSee($teacher->teacher_number)
        ->assertSee($teacher->first_name)
        ->assertSee($teacher->last_name);
});

it('allows staff users to view the teacher directory', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertSee($teacher->teacher_number);
});

it('displays the teachers navigation link to authorized users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertSee(route('teachers.index'), false);
});

it('displays the teacher registration link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertSee(route('teachers.create'), false);
});

it('hides the teacher registration link from staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertDontSee(route('teachers.create'), false);
});

it('allows administrators and staff to view teacher profiles', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('teachers.show', $teacher));

    $response->assertOk()
        ->assertSee($teacher->teacher_number)
        ->assertSee($teacher->first_name)
        ->assertSee($teacher->last_name)
        ->assertSee($teacher->email);
});

// Teacher registration

it('allows administrators to view the teacher registration form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('teachers.create'));

    $response->assertOk()
        ->assertSee('Teacher Registration');
});

it('prevents staff users from viewing the teacher registration form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teachers.create'));

    $response->assertForbidden();
});

it('allows administrators to register teachers', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-NEW-001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'phone' => '0712345678',
            'date_of_birth' => '1990-05-15',
            'employment_date' => '2020-02-10',
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $teacher = Teacher::query()
        ->where('teacher_number', 'TCH-NEW-001')
        ->firstOrFail();

    $response->assertRedirect(route('teachers.show', $teacher))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teachers', [
        'teacher_number' => 'TCH-NEW-001',
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john.smith@example.com',
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    expect($teacher->employment_date->toDateString())
        ->toBe('2020-02-10');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'teacher.created',
        'auditable_type' => $teacher->getMorphClass(),
        'auditable_id' => $teacher->getKey(),
        'user_id' => $admin->id,
    ]);
});

it('prevents staff users from registering teachers', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-UNAUTH-001',
            'first_name' => 'Unauthorized',
            'last_name' => 'Teacher',
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('teachers', [
        'teacher_number' => 'TCH-UNAUTH-001',
    ]);
});

it('rejects duplicate teacher numbers during registration', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-DUP-001',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-DUP-001',
            'first_name' => 'Another',
            'last_name' => 'Teacher',
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('teacher_number');

    $this->assertDatabaseCount('teachers', 1);
});

it('requires mandatory teacher registration fields', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), []);

    $response->assertSessionHasErrors([
        'teacher_number',
        'first_name',
        'last_name',
        'employment_date',
        'status',
    ]);
});

it('allows optional teacher information to be empty', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-OPTIONAL-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
            'email' => null,
            'phone' => null,
            'date_of_birth' => null,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('teachers', [
        'teacher_number' => 'TCH-OPTIONAL-001',
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
    ]);
});

it('rejects invalid email addresses during registration', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-EMAIL-001',
            'first_name' => 'Invalid',
            'last_name' => 'Email',
            'email' => 'invalid-email',
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('email');
});

it('rejects future dates of birth during registration', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-DOB-001',
            'first_name' => 'Future',
            'last_name' => 'Date',
            'date_of_birth' => now()->addDay()->toDateString(),
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $response->assertSessionHasErrors('date_of_birth');
});

it('rejects invalid teacher statuses during registration', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-STATUS-001',
            'first_name' => 'Invalid',
            'last_name' => 'Status',
            'employment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

    $response->assertSessionHasErrors('status');

    $this->assertDatabaseMissing('teachers', [
        'teacher_number' => 'TCH-STATUS-001',
    ]);
});

// Teacher editing

it('allows administrators to view the teacher editing form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('teachers.edit', $teacher));

    $response->assertOk()
        ->assertSee('Edit Teacher Information')
        ->assertSee($teacher->teacher_number);
});

it('prevents staff users from viewing the teacher editing form', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($staff)
        ->get(route('teachers.edit', $teacher));

    $response->assertForbidden();
});

it('allows administrators to update teacher information', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('teachers.update', $teacher), [
            'teacher_number' => 'TCH-UPDATED-001',
            'first_name' => 'Updated',
            'last_name' => 'Teacher',
            'email' => 'updated.teacher@example.com',
            'phone' => '0771234567',
            'date_of_birth' => '1988-06-20',
            'employment_date' => '2021-03-15',
            'status' => Teacher::STATUS_INACTIVE,
        ]);

    $response->assertRedirect(route('teachers.show', $teacher))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'teacher_number' => 'TCH-UPDATED-001',
        'first_name' => 'Updated',
        'last_name' => 'Teacher',
        'email' => 'updated.teacher@example.com',
        'status' => Teacher::STATUS_INACTIVE,
    ]);

    expect($teacher->fresh()->employment_date->toDateString())
        ->toBe('2021-03-15');

    $auditLog = AuditLog::query()
        ->where('event', 'teacher.updated')
        ->where('auditable_id', $teacher->getKey())
        ->firstOrFail();

    expect($auditLog->auditable_type)
        ->toBe($teacher->getMorphClass());

    expect($auditLog->user_id)
        ->toBe($admin->id);

    expect($auditLog->properties['changed_fields'])
        ->toContain('first_name')
        ->toContain('teacher_number')
        ->toContain('status');
});

it('allows a teacher to retain their existing teacher number during an update', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create([
        'teacher_number' => 'TCH-RETAIN-001',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('teachers.update', $teacher), [
            'teacher_number' => 'TCH-RETAIN-001',
            'first_name' => 'Updated Name',
            'last_name' => $teacher->last_name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'date_of_birth' => $teacher->date_of_birth?->format('Y-m-d'),
            'employment_date' => $teacher->employment_date->format('Y-m-d'),
            'status' => $teacher->status,
        ]);

    $response->assertRedirect(route('teachers.show', $teacher));

    expect($teacher->fresh()->teacher_number)
        ->toBe('TCH-RETAIN-001');
});

it('rejects teacher numbers already assigned to another teacher during updates', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $anotherTeacher = Teacher::factory()->create([
        'teacher_number' => 'TCH-EXISTING-001',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('teachers.update', $teacher), [
            'teacher_number' => 'TCH-EXISTING-001',
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'date_of_birth' => $teacher->date_of_birth?->format('Y-m-d'),
            'employment_date' => $teacher->employment_date->format('Y-m-d'),
            'status' => $teacher->status,
        ]);

    $response->assertSessionHasErrors('teacher_number');

    expect($teacher->fresh()->teacher_number)
        ->not->toBe($anotherTeacher->teacher_number);
});

it('prevents staff users from updating teachers', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($staff)
        ->put(route('teachers.update', $teacher), [
            'teacher_number' => $teacher->teacher_number,
            'first_name' => 'Unauthorized',
            'last_name' => 'Update',
            'employment_date' => now()->toDateString(),
            'status' => Teacher::STATUS_ACTIVE,
        ]);

    $response->assertForbidden();

    expect($teacher->fresh()->first_name)
        ->toBe($teacher->first_name);
});

// Teacher archiving

it('allows administrators to archive teachers successfully', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('teachers.destroy', $teacher));

    $response->assertRedirect(route('teachers.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('teachers', [
        'id' => $teacher->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'teacher.archived',
        'auditable_type' => $teacher->getMorphClass(),
        'auditable_id' => $teacher->getKey(),
        'user_id' => $admin->id,
    ]);
});

it('prevents staff users from archiving teachers', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($staff)
        ->delete(route('teachers.destroy', $teacher));

    $response->assertForbidden();

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'deleted_at' => null,
    ]);
});

it('keeps archived teachers out of the active directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $teacher->delete();

    $response = $this->actingAs($admin)
        ->get(route('teachers.index'));

    $response->assertOk()
        ->assertDontSee($teacher->teacher_number);
});

// Archived teachers and restoration

it('allows administrators to view archived teachers', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $teacher->delete();

    $response = $this->actingAs($admin)
        ->get(route('teachers.archived'));

    $response->assertOk()
        ->assertSee($teacher->teacher_number);
});

it('prevents staff users from viewing archived teachers', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->get(route('teachers.archived'));

    $response->assertForbidden();
});

it('allows administrators to restore archived teachers', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $teacherNumber = $teacher->teacher_number;

    $teacher->delete();

    $response = $this->actingAs($admin)
        ->post(route('teachers.restore', $teacher->id));

    $response->assertRedirect(
        route('teachers.show', $teacher->id)
    );

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'teacher_number' => $teacherNumber,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'teacher.restored',
        'auditable_type' => $teacher->getMorphClass(),
        'auditable_id' => $teacher->getKey(),
        'user_id' => $admin->id,
    ]);
});

it('prevents staff users from restoring archived teachers', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create();

    $teacher->delete();

    $response = $this->actingAs($staff)
        ->post(route('teachers.restore', $teacher->id));

    $response->assertForbidden();

    $this->assertSoftDeleted('teachers', [
        'id' => $teacher->id,
    ]);
});

it('returns a 404 when attempting to restore a teacher who is not archived', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $teacher = Teacher::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('teachers.restore', $teacher->id));

    $response->assertNotFound();

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'deleted_at' => null,
    ]);
});

// Teacher searching, filtering, and pagination

it('searches teachers by teacher number', function (): void {
    $teacher = Teacher::factory()->create([
        'teacher_number' => 'TCH-SEARCH-001',
    ]);

    $response = $this->get(route('teachers.index', [
        'search' => 'TCH-SEARCH-001',
    ]));

    $response->assertOk()
        ->assertSee('TCH-SEARCH-001');
});

it('searches teachers by first name and last name', function (): void {
    $teacher = Teacher::factory()->create([
        'first_name' => 'UniqueFirst',
        'last_name' => 'UniqueLast',
    ]);

    $response = $this->get(route('teachers.index', [
        'search' => 'UniqueFirst',
    ]));

    $response->assertOk()
        ->assertSee('UniqueFirst')
        ->assertSee('UniqueLast');
});

it('searches teachers by email address', function (): void {
    $teacher = Teacher::factory()->create([
        'email' => 'unique.teacher@example.com',
    ]);

    $response = $this->get(route('teachers.index', [
        'search' => 'unique.teacher@example.com',
    ]));

    $response->assertOk()
        ->assertSee('unique.teacher@example.com');
});

it('paginates the teacher directory with ten teachers per page', function (): void {
    Teacher::factory()->count(11)->create();

    $response = $this->get(route('teachers.index'));

    $response->assertOk()
        ->assertViewHas('teachers', function ($teachers): bool {
            return $teachers->total() === 11
                && $teachers->perPage() === 10;
        });
});

it('filters teachers by employment status', function (): void {
    Teacher::factory()->create([
        'teacher_number' => 'TCH-FILTER-ACTIVE',
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-FILTER-INACTIVE',
        'status' => Teacher::STATUS_INACTIVE,
    ]);

    $this->get(route('teachers.index', [
        'status' => Teacher::STATUS_ACTIVE,
    ]))
        ->assertOk()
        ->assertSee('TCH-FILTER-ACTIVE')
        ->assertDontSee('TCH-FILTER-INACTIVE');
});

it('filters teachers by employment date range inclusively', function (): void {
    Teacher::factory()->create([
        'teacher_number' => 'TCH-DATE-BEFORE',
        'employment_date' => '2026-01-09',
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-DATE-START',
        'employment_date' => '2026-01-10',
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-DATE-END',
        'employment_date' => '2026-01-20',
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-DATE-AFTER',
        'employment_date' => '2026-01-21',
    ]);

    $this->get(route('teachers.index', [
        'employed_from' => '2026-01-10',
        'employed_to' => '2026-01-20',
    ]))
        ->assertOk()
        ->assertSee('TCH-DATE-START')
        ->assertSee('TCH-DATE-END')
        ->assertDontSee('TCH-DATE-BEFORE')
        ->assertDontSee('TCH-DATE-AFTER');
});

it('combines teacher search status and date filters', function (): void {
    Teacher::factory()->create([
        'teacher_number' => 'TCH-COMBINED-MATCH',
        'first_name' => 'UniqueTeacherFilter',
        'status' => Teacher::STATUS_ACTIVE,
        'employment_date' => '2026-02-15',
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-COMBINED-STATUS',
        'first_name' => 'UniqueTeacherFilter',
        'status' => Teacher::STATUS_INACTIVE,
        'employment_date' => '2026-02-15',
    ]);

    Teacher::factory()->create([
        'teacher_number' => 'TCH-COMBINED-DATE',
        'first_name' => 'UniqueTeacherFilter',
        'status' => Teacher::STATUS_ACTIVE,
        'employment_date' => '2026-03-15',
    ]);

    $this->get(route('teachers.index', [
        'search' => 'UniqueTeacherFilter',
        'status' => Teacher::STATUS_ACTIVE,
        'employed_from' => '2026-02-01',
        'employed_to' => '2026-02-28',
    ]))
        ->assertOk()
        ->assertSee('TCH-COMBINED-MATCH')
        ->assertDontSee('TCH-COMBINED-STATUS')
        ->assertDontSee('TCH-COMBINED-DATE');
});

it('rejects invalid teacher filter values', function (): void {
    $this->get(route('teachers.index', [
        'status' => 'unknown',
    ]))->assertSessionHasErrors('status');

    $this->get(route('teachers.index', [
        'employed_from' => '2026-03-01',
        'employed_to' => '2026-02-01',
    ]))->assertSessionHasErrors('employed_to');
});

it('preserves teacher filters during pagination', function (): void {
    foreach (range(1, 11) as $number) {
        Teacher::factory()->create([
            'teacher_number' => sprintf('TCH-PAGED-%03d', $number),
            'status' => Teacher::STATUS_ACTIVE,
            'employment_date' => '2026-04-15',
        ]);
    }

    $this->get(route('teachers.index', [
        'status' => Teacher::STATUS_ACTIVE,
        'employed_from' => '2026-04-01',
        'page' => 2,
    ]))
        ->assertOk()
        ->assertViewHas('teachers', function ($teachers): bool {
            $url = $teachers->url(1);

            return $teachers->total() === 11
                && $teachers->currentPage() === 2
                && str_contains($url, 'status=active')
                && str_contains($url, 'employed_from=2026-04-01');
        });
});
