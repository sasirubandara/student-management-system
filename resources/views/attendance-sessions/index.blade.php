<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Management | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">

            <div>
                <h1>Attendance Management</h1>
                <p>Manage subject-based student attendance sessions.</p>
            </div>

            @can('create', \App\Models\AttendanceSession::class)
                <div class="students-page__header-actions">
                    <a href="{{ route('attendance-sessions.create') }}"
                       class="students-page__button">
                        Create Attendance Session
                    </a>
                </div>
            @endcan

        </header>

        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="students-search-section">

            <form action="{{ route('attendance-sessions.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">
                    <label for="attendance-search" class="sr-only">
                        Search attendance sessions
                    </label>

                    <input
                        type="search"
                        id="attendance-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search teacher, class, subject or date..."
                        maxlength="255"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('attendance-sessions.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching attendance sessions:' : 'Total attendance sessions:' }}
                <strong>{{ $attendanceSessions->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for: <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        @if ($attendanceSessions->isEmpty())

            <section class="students-page__empty">

                <h2>No Attendance Sessions Found</h2>

                @if ($search !== '')
                    <p>No sessions matched your search. Try another search term.</p>

                    <a href="{{ route('attendance-sessions.index') }}"
                       class="students-page__button">
                        View All Sessions
                    </a>
                @else
                    <p>There are currently no attendance sessions.</p>

                    @can('create', \App\Models\AttendanceSession::class)
                        <a href="{{ route('attendance-sessions.create') }}"
                           class="students-page__button">
                            Create First Session
                        </a>
                    @endcan
                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Teacher</th>
                            <th>Academic Class</th>
                            <th>Subject</th>
                            <th>Attendance Records</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($attendanceSessions as $session)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $session->attendance_date->format('d F Y') }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $session->teachingAssignment->teacher->teacher_number }}
                                    <br>
                                    {{ $session->teachingAssignment->teacher->first_name }}
                                    {{ $session->teachingAssignment->teacher->last_name }}
                                </td>

                                <td>
                                    {{ $session->teachingAssignment->academicClass->name }}
                                    <br>
                                    {{ $session->teachingAssignment->academicClass->code }}
                                </td>

                                <td>
                                    {{ $session->teachingAssignment->subject->name }}
                                    <br>
                                    {{ $session->teachingAssignment->subject->code }}
                                </td>

                                <td>
                                    {{ $session->student_attendances_count }}
                                </td>

                                <td>
                                    {{ $session->creator->name }}
                                </td>

                                <td>
                                    <a href="{{ route('attendance-sessions.show', $session) }}"
                                       class="students-table__view">
                                        View
                                    </a>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <div class="students-page__pagination">
                {{ $attendanceSessions->links() }}
            </div>

        @endif

    </main>

</body>
</html>