<?php

use App\Models\User;

// Account status display tests

it('displays active and inactive account statuses in the user directory', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    User::factory()->create([
        'name' => 'Active Status Staff',
        'email' => 'active-status@example.test',
        'is_active' => true,
    ]);

    User::factory()->create([
        'name' => 'Inactive Status Staff',
        'email' => 'inactive-status@example.test',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('users.index'));

    $response->assertOk()
        ->assertSee('Account Status')
        ->assertSee('Active Status Staff')
        ->assertSee('Inactive Status Staff')
        ->assertSee('Activate')
        ->assertSee('Deactivate');
});

// Account deactivation tests

it('allows administrators to deactivate staff accounts', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.status.update', $staff), [
            'is_active' => 0,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas(
            'success',
            'User account deactivated successfully.'
        );

    expect($staff->fresh()->is_active)->toBeFalse();

    $auditLog = \App\Models\AuditLog::query()
        ->where('event', 'user.status_updated')
        ->where('auditable_id', $staff->id)
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->id)
        ->and($auditLog->properties['changes']['is_active']['old'])->toBeTrue()
        ->and($auditLog->properties['changes']['is_active']['new'])->toBeFalse();
});

it('allows administrators to reactivate inactive accounts', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.status.update', $staff), [
            'is_active' => 1,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas(
            'success',
            'User account activated successfully.'
        );

    expect($staff->fresh()->is_active)->toBeTrue();

    $auditLog = \App\Models\AuditLog::query()
        ->where('event', 'user.status_updated')
        ->where('auditable_id', $staff->id)
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->id)
        ->and($auditLog->properties['changes']['is_active']['old'])->toBeFalse()
        ->and($auditLog->properties['changes']['is_active']['new'])->toBeTrue();
});

it('prevents staff users from changing account statuses', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $target = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $response = $this->actingAs($staff)
        ->patch(route('users.status.update', $target), [
            'is_active' => 0,
        ]);

    $response->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
});

it('prevents administrators from deactivating their own accounts', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.status.update', $admin), [
            'is_active' => 0,
        ]);

    $response->assertSessionHasErrors('is_active');

    expect($admin->fresh()->is_active)->toBeTrue();

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'user.status_updated',
        'auditable_id' => $admin->id,
    ]);
});

it('preserves another active administrator when deactivating an administrator account', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $targetAdmin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.status.update', $targetAdmin), [
            'is_active' => 0,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect($admin->fresh()->is_active)->toBeTrue()
        ->and($targetAdmin->fresh()->is_active)->toBeFalse();
});

it('prevents demoting the last active administrator when other administrator accounts are inactive', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('users.role.update', $admin), [
            'role' => User::ROLE_STAFF,
        ]);

    $response->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(User::ROLE_ADMIN)
        ->and($admin->fresh()->is_active)->toBeTrue();
});

// Login and middleware tests

it('prevents inactive users from logging in', function (): void {
    $inactiveUser = User::factory()->create([
        'email' => 'inactive-login@example.test',
        'password' => 'CorrectPassword123!',
        'is_active' => false,
    ]);

    // The global Pest setup automatically authenticates a user.
    // Log that user out before testing the login process.
    auth()->logout();

    $response = $this->post(route('login.store'), [
        'email' => $inactiveUser->email,
        'password' => 'CorrectPassword123!',
    ]);

    $response->assertRedirect()
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out an authenticated user when their account is inactive', function (): void {
    $inactiveUser = User::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->actingAs($inactiveUser)
        ->get(route('students.index'));

    $response->assertRedirect(route('login'))
        ->assertSessionHas(
            'status',
            'Your account is inactive. Please contact an administrator.'
        );

    $this->assertGuest();
});