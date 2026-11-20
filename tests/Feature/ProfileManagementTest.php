<?php

use App\Models\User;
use App\Services\ApplicationSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

afterEach(function (): void {
    Cache::forget(ApplicationSettingsService::CACHE_KEY);
});

it('allows administrators to view their own profile', function (): void {
    $admin = User::factory()->create([
        'name' => 'Profile Administrator',
        'email' => 'profile-admin@example.test',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertViewIs('profile.edit')
        ->assertViewHas('user', function (User $user) use ($admin): bool {
            return $user->is($admin);
        })
        ->assertSee('My Profile')
        ->assertSee('Profile Administrator')
        ->assertSee('profile-admin@example.test')
        ->assertSee('Administrator');
});

it('allows staff users to view their own profile', function (): void {
    $staff = User::factory()->create([
        'name' => 'Profile Staff Member',
        'email' => 'profile-staff@example.test',
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertViewIs('profile.edit')
        ->assertViewHas('user', function (User $user) use ($staff): bool {
            return $user->is($staff);
        })
        ->assertSee('Profile Staff Member')
        ->assertSee('profile-staff@example.test')
        ->assertSee('Staff');
});

it('requires authentication to view personal profiles', function (): void {
    $this->actingAsGuest();

    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('prevents inactive users from accessing their profiles', function (): void {
    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'))
        ->assertSessionHas(
            'status',
            'Your account is inactive. Please contact an administrator.'
        );

    $this->assertGuest();
});

it('ignores other user identifiers when displaying a profile', function (): void {
    $user = User::factory()->create([
        'name' => 'Authenticated Profile Owner',
        'email' => 'profile-owner@example.test',
    ]);

    $otherUser = User::factory()->create([
        'name' => 'Unrelated Profile Owner',
        'email' => 'unrelated-profile@example.test',
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit', [
            'user' => $otherUser->id,
            'user_id' => $otherUser->id,
        ]))
        ->assertOk()
        ->assertViewHas('user', function (User $profile) use ($user): bool {
            return $profile->is($user);
        })
        ->assertSee($user->email)
        ->assertDontSee($otherUser->email);
});

it('displays the profile navigation link to administrators', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('profile.edit'), false)
        ->assertSee('My Profile');
});

it('displays the profile navigation link to staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('profile.edit'), false)
        ->assertSee('My Profile');
});

it('displays read-only account information on the profile page', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
        'created_at' => '2026-01-15 10:00:00',
    ]);

    app(ApplicationSettingsService::class)->update([
        'date_format' => 'd/m/Y',
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Account Information')
        ->assertSee('Account Role')
        ->assertSee('Administrator')
        ->assertSee('Account Status')
        ->assertSee('Active')
        ->assertSee('Member Since')
        ->assertSee('15/01/2026')
        ->assertDontSee('name="role"', false)
        ->assertDontSee('name="is_active"', false);
});

it('allows administrators to update their profile information', function (): void {
    $admin = User::factory()->create([
        'name' => 'Original Administrator',
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Updated Administrator',
            'email' => $admin->email,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas(
            'success',
            'Profile information updated successfully.'
        );

    expect($admin->fresh()->name)
        ->toBe('Updated Administrator');
});

it('allows staff users to update their profile information', function (): void {
    $staff = User::factory()->create([
        'name' => 'Original Staff Member',
        'role' => User::ROLE_STAFF,
    ]);

    $this->actingAs($staff)
        ->patch(route('profile.update'), [
            'name' => 'Updated Staff Member',
            'email' => $staff->email,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    expect($staff->fresh()->name)
        ->toBe('Updated Staff Member');
});

it('preserves email verification when the email address is unchanged', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email_verified_at)
        ->toEqual($verifiedAt);
});

it('allows email changes after current password verification', function (): void {
    $user = User::factory()->create([
        'email' => 'original@example.test',
        'password' => Hash::make('OriginalPassword123!'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'updated@example.test',
            'current_password' => 'OriginalPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    $updatedUser = $user->fresh();

    expect($updatedUser->email)
        ->toBe('updated@example.test');

    expect($updatedUser->email_verified_at)
        ->toBeNull();

    expect(Hash::check(
        'OriginalPassword123!',
        $updatedUser->password
    ))->toBeTrue();
});

it('requires the current password when changing email addresses', function (): void {
    $user = User::factory()->create();

    $originalEmail = $user->email;

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'changed@example.test',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)
        ->toBe($originalEmail);
});

it('rejects email changes when the current password is incorrect', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $originalEmail = $user->email;
    $originalName = $user->name;

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Rejected Name',
            'email' => 'rejected@example.test',
            'current_password' => 'IncorrectPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)
        ->toBe($originalEmail);

    expect($user->fresh()->name)
        ->toBe($originalName);

    expect(session()->getOldInput('current_password'))
        ->toBeNull();
});

it('rejects duplicate email addresses during profile updates', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $otherUser = User::factory()->create();

    $originalEmail = $user->email;

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $otherUser->email,
            'current_password' => 'OriginalPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)
        ->toBe($originalEmail);
});

it('validates required profile information', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors([
            'name',
            'email',
        ]);
});

it('rejects invalid and oversized profile information', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => str_repeat('A', 256),
            'email' => 'invalid-email-address',
            'current_password' => 'OriginalPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors([
            'name',
            'email',
        ]);
});

it('ignores protected account attributes and other user identifiers during profile updates', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $otherUser = User::factory()->create([
        'name' => 'Unrelated Account',
        'role' => User::ROLE_ADMIN,
    ]);

    $originalPassword = $user->password;
    $originalToken = $user->remember_token;
    $originalCreatedAt = $user->created_at;
    $originalVerifiedAt = $user->email_verified_at;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Safe Profile Update',
            'email' => $user->email,

            'id' => $otherUser->id,
            'user_id' => $otherUser->id,
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
            'password' => 'UnauthorizedPassword123!',
            'remember_token' => 'unauthorized-token',
            'email_verified_at' => null,
            'created_at' => '2000-01-01 00:00:00',
        ])
        ->assertRedirect(route('profile.edit'));

    $updatedUser = $user->fresh();

    expect($updatedUser->name)
        ->toBe('Safe Profile Update');

    expect($updatedUser->role)
        ->toBe(User::ROLE_STAFF);

    expect($updatedUser->isActive())
        ->toBeTrue();

    expect($updatedUser->password)
        ->toBe($originalPassword);

    expect($updatedUser->remember_token)
        ->toBe($originalToken);

    expect($updatedUser->created_at)
        ->toEqual($originalCreatedAt);

    expect($updatedUser->email_verified_at)
        ->toEqual($originalVerifiedAt);

    expect($otherUser->fresh()->name)
        ->toBe('Unrelated Account');
});

it('requires authentication to update a personal profile', function (): void {
    $this->actingAsGuest();

    $this->patch(route('profile.update'), [
        'name' => 'Unauthorized Update',
        'email' => 'unauthorized@example.test',
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    $this->assertDatabaseMissing('users', [
        'email' => 'unauthorized@example.test',
    ]);
});

it('prevents inactive users from updating their profiles', function (): void {
    $user = User::factory()->create([
        'name' => 'Inactive Account',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Unauthorized Name',
            'email' => $user->email,
        ])
        ->assertRedirect(route('login'));

    $this->assertGuest();

    expect($user->fresh()->name)
        ->toBe('Inactive Account');
});
