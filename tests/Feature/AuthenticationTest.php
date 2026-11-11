<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('displays the login page to guests', function () {
    $this->actingAsGuest();

    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertViewIs('auth.login')
        ->assertSee('Welcome Back')
        ->assertSee('Sign In');
});

it('authenticates a user with valid credentials and redirects to the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.test',
        'password' => Hash::make('StrongPassword123!'),
    ]);

    $this->actingAsGuest();

    $response = $this->post(route('login.store'), [
        'email' => 'admin@example.test',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.login_succeeded',
        'user_id' => $user->id,
        'auditable_type' => null,
        'auditable_id' => null,
    ]);
});

it('rejects invalid login credentials', function () {
    $this->actingAsGuest();

    $response = $this->post(route('login.store'), [
        'email' => 'wrong@example.test',
        'password' => 'WrongPassword123!',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest();

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'auth.login_succeeded',
    ]);
});

it('redirects unauthenticated users from student management pages', function () {
    $this->actingAsGuest();

    $response = $this->get(route('students.index'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

it('logs out an authenticated user successfully', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('login'))
        ->assertSessionHas(
            'status',
            'You have been logged out successfully.'
        );

    $this->assertGuest();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.logged_out',
        'user_id' => $user->id,
        'auditable_type' => null,
        'auditable_id' => null,
    ]);
});

it('displays the authenticated user navigation and logout form', function () {
    $user = auth()->user();

    $response = $this->get(route('students.index'));

    $response->assertOk()
        ->assertSee('Student Management System')
        ->assertSee($user->name)
        ->assertSee('Staff')
        ->assertDontSee(route('students.archived'), false)
        ->assertSee('Logout')
        ->assertSee(route('logout'), false);
});