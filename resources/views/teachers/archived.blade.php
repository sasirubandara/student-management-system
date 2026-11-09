<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Archived Teachers | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Archived Teachers</h1>
                <p>View and restore previously archived teacher records.</p>
            </div>

            <div class="students-page__header-actions">

                <a href="{{ route('teachers.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Teachers
                </a>

            </div>

        </header>

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Archived teacher summary -->
        <section class="students-page__summary">

            <p>
                Total archived teachers:
                <strong>{{ $teachers->total() }}</strong>
            </p>

        </section>

        <!-- Archived teachers listing -->
        @if ($teachers->isEmpty())

            <section class="students-page__empty">

                <h2>No Archived Teachers</h2>

                <p>
                    There are currently no archived teacher records.
                </p>

                <a href="{{ route('teachers.index') }}"
                   class="students-page__button">
                    View Active Teachers
                </a>

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Teacher Number</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Employment Date</th>
                            <th>Archived On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($teachers as $teacher)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $teacher->teacher_number }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $teacher->first_name }}
                                    {{ $teacher->last_name }}
                                </td>

                                <td>
                                    {{ $teacher->email ?? '—' }}
                                </td>

                                <td>
                                    {{ $teacher->phone ?? '—' }}
                                </td>

                                <td>
                                    {{ $teacher->employment_date->format('d F Y') }}
                                </td>

                                <td>
                                    {{ $teacher->deleted_at?->format('d F Y, h:i A') ?? 'Not available' }}
                                </td>

                                <td>

                                    <form
                                        action="{{ route('teachers.restore', $teacher->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to restore this teacher?');"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="students-page__button"
                                        >
                                            Restore
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="students-page__pagination">
                {{ $teachers->links() }}
            </div>

        @endif

    </main>

</body>
</html>