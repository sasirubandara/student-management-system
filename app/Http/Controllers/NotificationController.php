<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the authenticated user's notifications.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'filter' => [
                'nullable',
                'string',
                Rule::in([
                    'all',
                    'unread',
                    'read',
                ]),
            ],
        ]);

        $filter = $validated['filter'] ?? 'all';

        $user = $request->user();

        $query = match ($filter) {
            'unread' => $user->unreadNotifications(),
            'read' => $user->readNotifications(),
            default => $user->notifications(),
        };

        $notifications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
        ]);
    }

    /**
     * Mark one of the authenticated user's notifications as read.
     */
    public function markAsRead(
        Request $request,
        string $notification
    ): RedirectResponse {
        $validated = $request->validate([
            'filter' => [
                'nullable',
                'string',
                Rule::in(['all', 'unread', 'read']),
            ],
        ]);

        $userNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($userNotification->read_at === null) {
            $userNotification->markAsRead();
        }

        $filter = $validated['filter'] ?? 'all';

        return redirect()
            ->route(
                'notifications.index',
                $filter === 'all' ? [] : ['filter' => $filter]
            )
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all of the authenticated user's unread notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'filter' => [
                'nullable',
                'string',
                Rule::in(['all', 'unread', 'read']),
            ],
        ]);

        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        $filter = $validated['filter'] ?? 'all';

        return redirect()
            ->route(
                'notifications.index',
                $filter === 'all' ? [] : ['filter' => $filter]
            )
            ->with('success', 'All notifications marked as read.');
    }
}
