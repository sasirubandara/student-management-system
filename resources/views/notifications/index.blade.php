<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications</title>
</head>
<body>

    <main>
        <h1>Notifications</h1>

        <p>
            Unread notifications: {{ $unreadCount }}
        </p>

        @if (session('success'))
            <p role="status">
                {{ session('success') }}
            </p>
        @endif

        @if ($unreadCount > 0)
            <form
                action="{{ route('notifications.read-all') }}"
                method="POST"
            >
                @csrf
                @method('PATCH')

                <button type="submit">
                    Mark All as Read
                </button>
            </form>
        @endif

        <section aria-label="Notification history">

            @forelse ($notifications as $notification)

                <article>
                    <h2>
                        {{ $notification->data['title'] ?? 'Notification' }}
                    </h2>

                    <p>
                        {{ $notification->data['message'] ?? '' }}
                    </p>

                    <p>
                        {{ $notification->created_at?->format('M d, Y h:i A') }}
                    </p>

                    @if ($notification->read_at)
                        <p>Read</p>
                    @else
                        <p>Unread</p>

                        <form
                            action="{{ route('notifications.read', $notification->id) }}"
                            method="POST"
                        >
                            @csrf
                            @method('PATCH')

                            <button type="submit">
                                Mark as Read
                            </button>
                        </form>
                    @endif
                </article>

                <hr>

            @empty

                <p>No notifications available.</p>

            @endforelse

        </section>

        {{ $notifications->links() }}

    </main>

</body>
</html>