<?php

use App\Models\User;
use App\Notifications\SystemActivityNotification;

it('stores system activity notifications in the database', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $notification = new SystemActivityNotification(
        event: 'student.created',
        title: 'New student registered',
        message: 'A new student record has been created.',
        url: '/students/15',
        icon: 'user-plus',
    );

    $user->notify($notification);

    $storedNotification = $user->notifications()->firstOrFail();

    expect($storedNotification->type)
        ->toBe('student.created');

    expect($storedNotification->data)
        ->toMatchArray([
            'event' => 'student.created',
            'title' => 'New student registered',
            'message' => 'A new student record has been created.',
            'url' => '/students/15',
            'icon' => 'user-plus',
        ]);

    expect($storedNotification->read_at)->toBeNull();
});

it('uses only the database notification channel', function (): void {
    $user = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $notification = new SystemActivityNotification(
        event: 'student.created',
        title: 'New student registered',
        message: 'A new student record has been created.',
    );

    expect($notification->via($user))
        ->toBe(['database']);
});
