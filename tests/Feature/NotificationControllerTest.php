<?php

use App\Models\User;
use App\Notifications\SystemActivityNotification;

it('allows users to view only their own notifications', function (): void {
    $recipient = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $anotherUser = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $recipient->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Recipient notification',
        message: 'This belongs to the authenticated user.',
    ));

    $anotherUser->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Private notification',
        message: 'This belongs to another user.',
    ));

    $ownNotification = $recipient->notifications()->firstOrFail();

    $response = $this->actingAs($recipient)
        ->get(route('notifications.index'));

    $response->assertOk()
        ->assertSee('Recipient notification')
        ->assertDontSee('Private notification')
        ->assertViewHas('unreadCount', 1)
        ->assertViewHas('notifications', function ($notifications) use (
            $ownNotification
        ): bool {
            return $notifications->total() === 1
                && $notifications->getCollection()
                    ->pluck('id')
                    ->all() === [$ownNotification->getKey()];
        });
});

it('allows users to mark their own notifications as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'New student registered',
        message: 'A new student record has been created.',
    ));

    $notification = $user->notifications()->firstOrFail();

    $response = $this->actingAs($user)
        ->patch(route('notifications.read', $notification->getKey()));

    $response->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('allows users to mark all their unread notifications as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $anotherUser = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'First notification',
        message: 'First notification message.',
    ));

    $user->notify(new SystemActivityNotification(
        event: 'enrollment.created',
        title: 'Second notification',
        message: 'Second notification message.',
    ));

    $anotherUser->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Another user notification',
        message: 'This belongs to another user.',
    ));

    $response = $this->actingAs($user)
        ->patch(route('notifications.read-all'));

    $response->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success');

    expect($user->unreadNotifications()->count())->toBe(0);

    expect($anotherUser->unreadNotifications()->count())->toBe(1);
});

it('prevents users from marking another users notification as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $anotherUser = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $anotherUser->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Private notification',
        message: 'This belongs to another user.',
    ));

    $notification = $anotherUser->notifications()->firstOrFail();

    $response = $this->actingAs($user)
        ->patch(route('notifications.read', $notification->getKey()));

    $response->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('requires authentication to access notification routes', function (): void {
    $this->actingAsGuest();

    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));

    $this->patch(route('notifications.read-all'))
        ->assertRedirect(route('login'));
});
