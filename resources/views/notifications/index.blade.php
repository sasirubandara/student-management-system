<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="notifications-page">

        <header class="notifications-page__header">
            <div>
                <h1>Notification Center</h1>

                <p>
                    Review updates and activity in your
                    Student Management System.
                </p>
            </div>

            @if ($unreadCount > 0)
                <form
                    action="{{ route('notifications.read-all') }}"
                    method="POST"
                >
                    @csrf
                    @method('PATCH')

                    @if ($filter !== 'all')
                        <input
                            type="hidden"
                            name="filter"
                            value="{{ $filter }}"
                        >
                    @endif

                    <button
                        type="submit"
                        class="notifications-page__button"
                    >
                        Mark All as Read
                    </button>
                </form>
            @endif
        </header>

        @if (session('success'))
            <div
                class="notifications-page__success"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        <section
            class="notifications-page__summary"
            aria-label="Notification summary"
        >
            <div>
                <span class="notifications-page__summary-label">
                    Unread Notifications
                </span>

                <strong>{{ number_format($unreadCount) }}</strong>
            </div>

            <div>
                <span class="notifications-page__summary-label">
                    Matching Notifications
                </span>

                <strong>{{ number_format($notifications->total()) }}</strong>
            </div>
        </section>

        <nav
            class="notifications-filters"
            aria-label="Notification filters"
        >
            <a
                href="{{ route('notifications.index', ['filter' => 'all']) }}"
                class="notifications-filters__link {{ $filter === 'all' ? 'notifications-filters__link--active' : '' }}"
                @if ($filter === 'all') aria-current="page" @endif
            >
                All
            </a>

            <a
                href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                class="notifications-filters__link {{ $filter === 'unread' ? 'notifications-filters__link--active' : '' }}"
                @if ($filter === 'unread') aria-current="page" @endif
            >
                Unread ({{ number_format($unreadCount) }})
            </a>

            <a
                href="{{ route('notifications.index', ['filter' => 'read']) }}"
                class="notifications-filters__link {{ $filter === 'read' ? 'notifications-filters__link--active' : '' }}"
                @if ($filter === 'read') aria-current="page" @endif
            >
                Read
            </a>
        </nav>

        <section aria-label="Notification history">

            <div class="notifications-list">

                @forelse ($notifications as $notification)

                    @php
                        $isUnread = $notification->read_at === null;

                        $title = $notification->data['title']
                            ?? 'Notification';

                        $message = $notification->data['message']
                            ?? '';

                        $event = $notification->data['event']
                            ?? $notification->type;
                    @endphp

                    <article
                        class="notification-card {{ $isUnread ? 'notification-card--unread' : 'notification-card--read' }}"
                    >
                        <div
                            class="notification-card__icon"
                            aria-hidden="true"
                        >
                            🔔
                        </div>

                        <div class="notification-card__content">

                            <div class="notification-card__heading">

                                <div>
                                    <h2 class="notification-card__title">
                                        {{ $title }}
                                    </h2>

                                    <span class="notification-card__event">
                                        {{ str_replace(['.', '_'], ' ', $event) }}
                                    </span>
                                </div>

                                <span
                                    class="notification-card__status {{ $isUnread ? 'notification-card__status--unread' : 'notification-card__status--read' }}"
                                >
                                    {{ $isUnread ? 'Unread' : 'Read' }}
                                </span>

                            </div>

                            <p class="notification-card__message">
                                {{ $message }}
                            </p>

                            <div class="notification-card__metadata">
                                <time
                                    datetime="{{ $notification->created_at?->toIso8601String() }}"
                                    title="{{ $notification->created_at?->format('M d, Y h:i A') }}"
                                >
                                    {{ $notification->created_at?->diffForHumans() }}
                                </time>
                            </div>

                            @if ($isUnread)
                                <div class="notification-card__actions">

                                    <form
                                        action="{{ route('notifications.read', $notification->id) }}"
                                        method="POST"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        @if ($filter !== 'all')
                                            <input
                                                type="hidden"
                                                name="filter"
                                                value="{{ $filter }}"
                                            >
                                        @endif

                                        <button
                                            type="submit"
                                            class="notifications-page__mark-read"
                                        >
                                            Mark as Read
                                        </button>
                                    </form>

                                </div>
                            @endif

                        </div>

                    </article>

                @empty

                    <div class="notifications-page__empty">

                        <div
                            class="notifications-page__empty-icon"
                            aria-hidden="true"
                        >
                            🔔
                        </div>

                        <h2>
                            @if ($filter === 'unread')
                                No unread notifications
                            @elseif ($filter === 'read')
                                No read notifications
                            @else
                                No notifications yet
                            @endif
                        </h2>

                        <p>
                            @if ($filter === 'unread')
                                You're all caught up! There are no unread
                                notifications available.
                            @elseif ($filter === 'read')
                                No read notifications available.
                            @else
                                No notifications available.
                                Activity updates will appear here.
                            @endif
                        </p>

                    </div>

                @endforelse

            </div>

        </section>

        @if ($notifications->hasPages())
            <div class="notifications-page__pagination">
                {{ $notifications->links() }}
            </div>
        @endif

    </main>

</body>
</html>