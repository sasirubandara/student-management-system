<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Activity Details</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <div>
                <h1>Audit Activity Details</h1>

                <p>
                    Review the details of this recorded application activity.
                </p>
            </div>

            <div class="page-header__actions">
                <a
                    href="{{ route('audit-logs.index') }}"
                    class="students-page__button"
                >
                    Back to Audit Logs
                </a>
            </div>
        </header>

        <section class="page-section">

            <h2>Activity Information</h2>

            <table class="students-table">

                <tbody>

                    <tr>
                        <th>Audit Record ID</th>
                        <td>{{ $auditLog->id }}</td>
                    </tr>

                    <tr>
                        <th>Activity</th>
                        <td>
                            {{ ucfirst(str_replace('.', ' ', $auditLog->event)) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Event Identifier</th>
                        <td>{{ $auditLog->event }}</td>
                    </tr>

                    <tr>
                        <th>Performed By</th>
                        <td>
                            {{ $auditLog->user?->name ?? 'Deleted user / System' }}
                        </td>
                    </tr>

                    <tr>
                        <th>User ID</th>
                        <td>{{ $auditLog->user_id ?? 'Unavailable' }}</td>
                    </tr>

                    <tr>
                        <th>Affected Record Type</th>
                        <td>
                            {{ $auditLog->auditable_type
                                ? class_basename($auditLog->auditable_type)
                                : 'System activity' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Affected Record ID</th>
                        <td>{{ $auditLog->auditable_id ?? 'Not applicable' }}</td>
                    </tr>

                    <tr>
                        <th>Date and Time</th>
                        <td>
                            {{ $auditLog->created_at->format('Y-m-d H:i:s') }}
                        </td>
                    </tr>

                    <tr>
                        <th>IP Address</th>
                        <td>{{ $auditLog->ip_address ?? 'Unavailable' }}</td>
                    </tr>

                    <tr>
                        <th>User Agent</th>
                        <td>
                            {{ $auditLog->user_agent ?? 'Unavailable' }}
                        </td>
                    </tr>

                </tbody>

            </table>

        </section>

        <section class="page-section">

            <h2>Activity Properties</h2>

            @if (! empty($auditLog->properties))

                <pre class="audit-log-properties">{{ json_encode(
                    $auditLog->properties,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ) }}</pre>

            @else

                <p>No additional activity properties were recorded.</p>

            @endif

        </section>

        <p>
            Audit records are read-only and cannot be modified or deleted
            through this interface.
        </p>

    </main>

</body>
</html>