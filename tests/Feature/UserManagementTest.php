<?php

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

// User directory tests

it('allows administrators to view the user directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'name' => 'Directory Test Staff',
        'email' => 'directory-staff@example.test',
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('users.index'));

    $response->assertOk()
        ->assertSee($staff->name)
        ->assertSee($staff->email)
        ->assertSee('Staff');
});

it('prevents staff users from viewing the user directory', function (): void {
    $response = $this->get(route('users.index'));

    $response->assertForbidden();
});

it('hides the users navigation link from staff users', function (): void {
    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertDontSee(route('users.index'), false);
});

it('shows the users navigation link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('students.index'));

    $response->assertOk()
        ->assertSee(route('users.index'), false);
});

it('searches users by name', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $matchingUser = User::factory()->create([
        'name' => 'Unique Directory Match',
    ]);

    $otherUser = User::factory()->create([
        'name' => 'Different Directory Person',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('users.index', [
            'search' => 'Unique Directory Match',
        ]));

    $response->assertOk()
        ->assertSee($matchingUser->name)
        ->assertDontSee($otherUser->name);
});

it('searches users by email', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $matchingUser = User::factory()->create([
        'name' => 'Email Search User',
        'email' => 'unique-directory-search@example.test',
    ]);

    $otherUser = User::factory()->create([
        'name' => 'Other Email User',
        'email' => 'other-directory@example.test',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('users.index', [
            'search' => 'unique-directory-search@example.test',
        ]));

    $response->assertOk()
        ->assertSee($matchingUser->name)
        ->assertDontSee($otherUser->name);
});

it('paginates the user directory with ten users per page', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    User::factory()->count(11)->create();

    $response = $this->actingAs($admin)
        ->get(route('users.index'));

    $response->assertOk()
        ->assertViewHas('users', function (LengthAwarePaginator $users): bool {
            return $users->perPage() === 10
                && $users->total() === 13;
        });
});

// Staff account creation tests

it('allows administrators to view the staff account creation form', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('users.create'));

    $response->assertOk()
        ->assertSee('Create Staff Account');
});

it('allows administrators to create staff accounts securely', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New Staff Member',
            'email' => 'new-staff@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',

            // Attempted role escalation must be ignored.
            'role' => User::ROLE_ADMIN,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $createdUser = User::where('email', 'new-staff@example.test')->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser->name)->toBe('New Staff Member')
        ->and($createdUser->role)->toBe(User::ROLE_STAFF)
        ->and(Hash::check('SecurePassword123!', $createdUser->password))->toBeTrue();
});

it('prevents staff users from viewing the account creation form', function (): void {
    $response = $this->get(route('users.create'));

    $response->assertForbidden();
});

it('prevents staff users from creating accounts', function (): void {
    $response = $this->post(route('users.store'), [
        'name' => 'Unauthorized Account',
        'email' => 'unauthorized@example.test',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('users', [
        'email' => 'unauthorized@example.test',
    ]);
});

it('rejects duplicate email addresses when creating staff accounts', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $existingUser = User::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Duplicate Email User',
            'email' => $existingUser->email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

    $response->assertSessionHasErrors('email');
});

it('requires password confirmation when creating staff accounts', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Password Confirmation User',
            'email' => 'password-confirmation@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

    $response->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', [
        'email' => 'password-confirmation@example.test',
    ]);
});

// Administrator role management tests

it('allows administrators to promote staff users to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.role.update', $staff), [
            'role' => User::ROLE_ADMIN,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect($staff->fresh()->role)->toBe(User::ROLE_ADMIN);
});

it('allows administrators to demote another administrator when another administrator remains', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $targetAdmin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.role.update', $targetAdmin), [
            'role' => User::ROLE_STAFF,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect($targetAdmin->fresh()->role)->toBe(User::ROLE_STAFF)
        ->and($admin->fresh()->role)->toBe(User::ROLE_ADMIN);
});

it('prevents the last administrator from being demoted', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.role.update', $admin), [
            'role' => User::ROLE_STAFF,
        ]);

    $response->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(User::ROLE_ADMIN);
});

it('prevents staff users from changing user roles', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $target = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($staff)
        ->patch(route('users.role.update', $target), [
            'role' => User::ROLE_ADMIN,
        ]);

    $response->assertForbidden();

    expect($target->fresh()->role)->toBe(User::ROLE_STAFF);
});

it('rejects invalid user roles', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.role.update', $staff), [
            'role' => 'superadmin',
        ]);

    $response->assertSessionHasErrors('role');

    expect($staff->fresh()->role)->toBe(User::ROLE_STAFF);
});