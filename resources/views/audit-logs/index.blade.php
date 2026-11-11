<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <div>
                <h1>Audit Logs</h1>

                <p>
                    Review important activities performed within
                    the Student Management System.
                </p>
            </div>
        </header>

        <section class="page-section">

            <h2>Search and Filter Activities</h2>

            <form
                action="{{ route('audit-logs.index') }}"
                method="GET"
            >

                <div class="form-group">
                    <label for="search">Search</label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ $validated['search'] ?? '' }}"
                        placeholder="Search events, record IDs, or users"
                    >
                </div>

                <div class="form-group">
                    <label for="event">Activity</label>

                    <select name="event" id="event">
                        <option value="">All activities</option>

                        @foreach ($events as $event)
                            <option
                                value="{{ $event }}"
                                @selected(($validated['event'] ?? '') === $event)
                            >
                                {{ ucfirst(str_replace('.', ' ', $event)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="module">Module</label>

                    <select name="module" id="module">
                        <option value="">All modules</option>

                        @foreach ($modules as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected(($validated['module'] ?? '') === $key)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="user_id">Performed By</label>

                    <select name="user_id" id="user_id">
                        <option value="">All users</option>

                        @foreach ($users as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected((string) ($validated['user_id'] ?? '') === (string) $user->id)
                            >
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_from">From Date</label>

                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        value="{{ $validated['date_from'] ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label for="date_to">To Date</label>

                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        value="{{ $validated['date_to'] ?? '' }}"
                    >
                </div>

                <button
                    type="submit"
                    class="students-page__button"
                >
                    Apply Filters
                </button>

                <a
                    href="{{ route('audit-logs.index') }}"
                    class="students-page__button"
                >
                    Clear Filters
                </a>

            </form>

        </section>

        <section class="page-section">

            <div class="page-header">
                <div>
                    <h2>Activity History</h2>

                    <p>
                        {{ number_format($auditLogs->total()) }}
                        activity record(s) found.
                    </p>
                </div>
            </div>

            @if ($auditLogs->count() > 0)

                <div class="table-responsive">

                    <table class="students-table">

                        <thead>
                            <tr>
                                <th>Date and Time</th>
                                <th>Performed By</th>
                                <th>Activity</th>
                                <th>Affected Record</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($auditLogs as $auditLog)

                                <tr>
                                    <td>
                                        {{ $auditLog->created_at->format('Y-m-d H:i:s') }}
                                    </td>

                                    <td>
                                        {{ $auditLog->user?->name ?? 'Deleted user / System' }}
                                    </td>

                                    <td>
                                        {{ ucfirst(str_replace('.', ' ', $auditLog->event)) }}
                                    </td>

                                    <td>
                                        @if ($auditLog->auditable_type)
                                            {{ class_basename($auditLog->auditable_type) }}

                                            @if ($auditLog->auditable_id)
                                                #{{ $auditLog->auditable_id }}
                                            @endif
                                        @else
                                            System activity
                                        @endif
                                    </td>

                                    <td>
                                        <a
                                            href="{{ route('audit-logs.show', $auditLog) }}"
                                        >
                                            View Details
                                        </a>
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                {{ $auditLogs->links() }}

            @else

                <p>No audit activities were found.</p>

            @endif

        </section>

    </main>

</body>
</html>