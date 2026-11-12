<?php

use App\Models\User;
use App\Services\NotificationService;

it('notifies other active users and excludes the acting user', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $anotherAdmin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $inactiveStaff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => false,
    ]);

    $notifiedCount = app(NotificationService::class)
        ->notifyOtherActiveUsers(
            actor: $admin,
            event: 'student.created',
            title: 'New student registered',
            message: 'A new student record has been created.',
            url: '/students',
            icon: 'user-plus',
        );

    expect($notifiedCount)->toBeGreaterThanOrEqual(2);

    expect($admin->notifications()->count())->toBe(0);

    expect($staff->notifications()->count())->toBe(1);

    expect($anotherAdmin->notifications()->count())->toBe(1);

    expect($inactiveStaff->notifications()->count())->toBe(0);

    $notification = $staff->notifications()->firstOrFail();

    expect($notification->type)->toBe('student.created');

    expect($notification->data)->toMatchArray([
        'event' => 'student.created',
        'title' => 'New student registered',
        'message' => 'A new student record has been created.',
        'url' => '/students',
        'icon' => 'user-plus',
    ]);
});

it('restricts notification recipients to the specified roles', function (): void {
    $actor = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $anotherAdmin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $notifiedCount = app(NotificationService::class)
        ->notifyOtherActiveUsers(
            actor: $actor,
            event: 'examination.created',
            title: 'New examination created',
            message: 'A new examination has been scheduled.',
            icon: 'clipboard-list',
            recipientRoles: [User::ROLE_STAFF],
        );

    expect($notifiedCount)->toBeGreaterThanOrEqual(1);

    expect($staff->notifications()->count())->toBe(1);

    expect($anotherAdmin->notifications()->count())->toBe(0);

    expect($actor->notifications()->count())->toBe(0);
});

it('returns zero when no notification recipients match the criteria', function (): void {
    $actor = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $notifiedCount = app(NotificationService::class)
        ->notifyOtherActiveUsers(
            actor: $actor,
            event: 'student.created',
            title: 'New student registered',
            message: 'A new student record has been created.',
            recipientRoles: [],
        );

    expect($notifiedCount)->toBe(0);

    expect($actor->notifications()->count())->toBe(0);
});
