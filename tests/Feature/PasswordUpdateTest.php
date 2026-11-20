<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function makePasswordUpdateUser(array $attributes = []): User
{
    return User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
        'is_active' => true,
        ...$attributes,
    ]);
}

function validPasswordUpdatePayload(): array
{
    return [
        'current_password' => 'OriginalPassword123!',
        'password' => 'UpdatedPassword456!',
        'password_confirmation' => 'UpdatedPassword456!',
    ];
}

it('allows administrators to change their passwords', function (): void {
    $admin = makePasswordUpdateUser([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->put(
            route('profile.password.update'),
            validPasswordUpdatePayload()
        )
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas(
            'password_success',
            'Your password has been updated successfully.'
        );

    expect(Hash::check(
        'UpdatedPassword456!',
        $admin->fresh()->password
    ))->toBeTrue();

    $this->assertAuthenticatedAs($admin);
});

it('allows staff users to change their passwords', function (): void {
    $staff = makePasswordUpdateUser([
        'role' => User::ROLE_STAFF,
    ]);

    $this->actingAs($staff)
        ->put(
            route('profile.password.update'),
            validPasswordUpdatePayload()
        )
        ->assertRedirect(route('profile.edit'));

    expect(Hash::check(
        'UpdatedPassword456!',
        $staff->fresh()->password
    ))->toBeTrue();

    $this->assertAuthenticatedAs($staff);
});

it('stores updated passwords securely as hashes', function (): void {
    $user = makePasswordUpdateUser();

    $originalHash = $user->password;

    $this->actingAs($user)
        ->put(
            route('profile.password.update'),
            validPasswordUpdatePayload()
        )
        ->assertRedirect(route('profile.edit'));

    $updatedPassword = $user->fresh()->password;

    expect($updatedPassword)
        ->not->toBe('UpdatedPassword456!')
        ->not->toBe($originalHash);

    expect(Hash::check(
        'UpdatedPassword456!',
        $updatedPassword
    ))->toBeTrue();

    expect(Hash::check(
        'OriginalPassword123!',
        $updatedPassword
    ))->toBeFalse();
});

it('requires the current password to change a password', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'password' => 'UpdatedPassword456!',
            'password_confirmation' => 'UpdatedPassword456!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['current_password']
        );
});

it('rejects incorrect current passwords', function (): void {
    $user = makePasswordUpdateUser();

    $originalHash = $user->password;
    $originalToken = $user->remember_token;

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            ...validPasswordUpdatePayload(),
            'current_password' => 'IncorrectPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['current_password']
        );

    expect($user->fresh()->password)
        ->toBe($originalHash);

    expect($user->fresh()->remember_token)
        ->toBe($originalToken);
});

it('requires a new password', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'current_password' => 'OriginalPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['password']
        );
});

it('rejects passwords shorter than twelve characters', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'current_password' => 'OriginalPassword123!',
            'password' => 'Short123!',
            'password_confirmation' => 'Short123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['password']
        );
});

it('requires matching new password confirmation', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            ...validPasswordUpdatePayload(),
            'password_confirmation' => 'DifferentPassword789!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['password']
        );
});

it('rejects reusing the current password', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'current_password' => 'OriginalPassword123!',
            'password' => 'OriginalPassword123!',
            'password_confirmation' => 'OriginalPassword123!',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['password']
        );
});

it('rotates the remember token after changing a password', function (): void {
    $user = makePasswordUpdateUser();

    $originalToken = $user->remember_token;

    $this->actingAs($user)
        ->put(
            route('profile.password.update'),
            validPasswordUpdatePayload()
        )
        ->assertRedirect(route('profile.edit'));

    $newToken = $user->fresh()->remember_token;

    expect($newToken)
        ->not->toBeNull()
        ->not->toBe($originalToken)
        ->toHaveLength(60);
});

it('protects other account attributes during password updates', function (): void {
    $user = makePasswordUpdateUser([
        'role' => User::ROLE_STAFF,
    ]);

    $otherUser = makePasswordUpdateUser();

    $originalName = $user->name;
    $originalEmail = $user->email;
    $otherUserPassword = $otherUser->password;

    $this->actingAs($user)
        ->put(route('profile.password.update'), [
            ...validPasswordUpdatePayload(),

            'id' => $otherUser->id,
            'user_id' => $otherUser->id,
            'name' => 'Unauthorized Name',
            'email' => 'unauthorized@example.test',
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
        ])
        ->assertRedirect(route('profile.edit'));

    $updatedUser = $user->fresh();

    expect($updatedUser->name)
        ->toBe($originalName);

    expect($updatedUser->email)
        ->toBe($originalEmail);

    expect($updatedUser->role)
        ->toBe(User::ROLE_STAFF);

    expect($updatedUser->isActive())
        ->toBeTrue();

    expect($otherUser->fresh()->password)
        ->toBe($otherUserPassword);
});

it('requires authentication to change passwords', function (): void {
    $this->actingAsGuest();

    $this->put(
        route('profile.password.update'),
        validPasswordUpdatePayload()
    )->assertRedirect(route('login'));

    $this->assertGuest();
});

it('prevents inactive users from changing passwords', function (): void {
    $user = makePasswordUpdateUser([
        'is_active' => false,
    ]);

    $originalHash = $user->password;

    $this->actingAs($user)
        ->put(
            route('profile.password.update'),
            validPasswordUpdatePayload()
        )
        ->assertRedirect(route('login'));

    $this->assertGuest();

    expect($user->fresh()->password)
        ->toBe($originalHash);
});

it('does not flash password values into the session', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            ...validPasswordUpdatePayload(),
            'current_password' => 'IncorrectPassword123!',
        ])
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['current_password']
        );

    expect(session()->getOldInput('current_password'))
        ->toBeNull();

    expect(session()->getOldInput('password'))
        ->toBeNull();

    expect(session()->getOldInput('password_confirmation'))
        ->toBeNull();
});

it('separates password errors from personal profile errors', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            ...validPasswordUpdatePayload(),
            'current_password' => 'IncorrectPassword123!',
        ])
        ->assertSessionHasErrorsIn(
            'updatePassword',
            ['current_password']
        );

    expect(session('errors')->getBag('default')->any())
        ->toBeFalse();
});

it('displays the password management form', function (): void {
    $user = makePasswordUpdateUser();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Password and Security')
        ->assertSee('Current Password')
        ->assertSee('New Password')
        ->assertSee('Confirm New Password')
        ->assertSee('Update Password')
        ->assertSee(route('profile.password.update'), false);
});
