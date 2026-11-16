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

it('filters notifications by unread status', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Unread notification',
        message: 'This notification is unread.',
    ));

    $user->notify(new SystemActivityNotification(
        event: 'student.updated',
        title: 'Read notification',
        message: 'This notification has been read.',
    ));

    $readNotification = $user->notifications()
        ->where('data->title', 'Read notification')
        ->firstOrFail();

    $readNotification->markAsRead();

    $this->actingAs($user)
        ->get(route('notifications.index', [
            'filter' => 'unread',
        ]))
        ->assertOk()
        ->assertSee('Unread notification')
        ->assertDontSee('Read notification')
        ->assertViewHas('filter', 'unread')
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 1;
        });
});

it('filters notifications by read status', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Pending item',
        message: 'This item is unread.',
    ));

    $user->notify(new SystemActivityNotification(
        event: 'student.updated',
        title: 'Completed item',
        message: 'This item has been read.',
    ));

    $notification = $user->notifications()
        ->where('data->title', 'Completed item')
        ->firstOrFail();

    $notification->markAsRead();

    $this->actingAs($user)
        ->get(route('notifications.index', [
            'filter' => 'read',
        ]))
        ->assertOk()
        ->assertSee('Completed item')
        ->assertDontSee('Pending item')
        ->assertViewHas('filter', 'read')
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 1;
        });
});

it('rejects invalid notification filters', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index', [
            'filter' => 'invalid',
        ]))
        ->assertSessionHasErrors('filter');
});

it('preserves the unread filter during notification pagination', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    for ($i = 1; $i <= 18; $i++) {
        $user->notify(new SystemActivityNotification(
            event: 'student.created',
            title: "Notification {$i}",
            message: 'Pagination test.',
        ));
    }

    $this->actingAs($user)
        ->get(route('notifications.index', [
            'filter' => 'unread',
        ]))
        ->assertOk()
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 18
                && $notifications->count() === 15;
        })
        ->assertSee('filter=unread', false);

    $this->actingAs($user)
        ->get(route('notifications.index', [
            'filter' => 'unread',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 18
                && $notifications->count() === 3
                && $notifications->currentPage() === 2;
        });
});

it('requires authentication to access notification routes', function (): void {
    $this->app['auth']->forgetGuards();

    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));

    $this->patch(route('notifications.read-all'))
        ->assertRedirect(route('login'));
});

it('preserves the unread filter after marking a notification as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'New student registered',
        message: 'A new student was registered.',
    ));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->patch(
            route('notifications.read', $notification->id),
            ['filter' => 'unread']
        )
        ->assertRedirect(route('notifications.index', [
            'filter' => 'unread',
        ]));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('preserves the active filter after marking all notifications as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'New student registered',
        message: 'A student record was created.',
    ));

    $this->actingAs($user)
        ->patch(
            route('notifications.read-all'),
            ['filter' => 'unread']
        )
        ->assertRedirect(route('notifications.index', [
            'filter' => 'unread',
        ]));

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('renders the notification center with authenticated navigation', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Student notification',
        message: 'A student record was created.',
    ));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Notification Center')
        ->assertSee('Student Management System')
        ->assertSee('Student notification')
        ->assertSee('notification-card--unread')
        ->assertSee('Mark as Read')
        ->assertSee('Mark All as Read');
});

it('rejects invalid filters during notification read operations', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Security test',
        message: 'Notification filter validation.',
    ));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->from(route('notifications.index'))
        ->patch(
            route('notifications.read', $notification->id),
            ['filter' => 'invalid']
        )
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHasErrors('filter');

    expect($notification->fresh()->read_at)->toBeNull();

    $this->actingAs($user)
        ->from(route('notifications.index'))
        ->patch(
            route('notifications.read-all'),
            ['filter' => 'invalid']
        )
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHasErrors('filter');

    expect($user->unreadNotifications()->count())->toBe(1);
});

it('safely handles marking an already-read notification as read', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: 'Already read',
        message: 'This notification was read earlier.',
    ));

    $notification = $user->notifications()->firstOrFail();

    $notification->markAsRead();

    $originalReadAt = $notification->fresh()->read_at;

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect(route('notifications.index'));

    expect($notification->fresh()->read_at->equalTo($originalReadAt))
        ->toBeTrue();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('handles marking all notifications as read when none are unread', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success');

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('escapes HTML in notification content', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $user->notify(new SystemActivityNotification(
        event: 'student.created',
        title: '<script>alert(1)</script>',
        message: '<strong>Example message</strong>',
    ));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('<script>alert(1)</script>')
        ->assertSee('<strong>Example message</strong>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<strong>Example message</strong>', false);
});
