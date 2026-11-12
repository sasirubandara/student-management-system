<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemActivityNotification;

class NotificationService
{
    /**
     * Send an in-app notification to other active users.
     *
     * The acting user is excluded from the recipient list.
     *
     * @param  array<int, string>|null  $recipientRoles
     */
    public function notifyOtherActiveUsers(
        User $actor,
        string $event,
        string $title,
        string $message,
        ?string $url = null,
        string $icon = 'bell',
        ?array $recipientRoles = null,
    ): int {
        $recipients = User::query()
            ->where('is_active', true)
            ->where(
                $actor->getKeyName(),
                '!=',
                $actor->getKey()
            );

        // Optionally restrict recipients to selected roles.
        if ($recipientRoles !== null) {
            $recipients->whereIn('role', $recipientRoles);
        }

        $notification = new SystemActivityNotification(
            event: $event,
            title: $title,
            message: $message,
            url: $url,
            icon: $icon,
        );

        $notifiedCount = 0;

        // Process recipients in batches to avoid loading
        // every active user into memory at once.
        $recipients->chunkById(100, function ($users) use (
            $notification,
            &$notifiedCount
        ): void {
            foreach ($users as $user) {
                $user->notify($notification);

                $notifiedCount++;
            }
        });

        return $notifiedCount;
    }
}
