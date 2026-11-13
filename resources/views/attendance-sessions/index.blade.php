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
            <form
                action="{{ route('attendance-sessions.index') }}"
                method="GET"
                class="students-search advanced-filters"
            >
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

                <div>
                    <label for="attendance-teacher">Teacher</label>
                    <select id="attendance-teacher" name="teacher_id">
                        <option value="">All teachers</option>

                        @foreach ($teachers as $teacher)
                            <option
                                value="{{ $teacher->id }}"
                                @selected((string) $teacherId === (string) $teacher->id)
                            >
                                {{ $teacher->teacher_number }}
                                — {{ $teacher->first_name }} {{ $teacher->last_name }}
                                @if ($teacher->trashed())
                                    (Archived)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendance-class">Academic class</label>
                    <select id="attendance-class" name="academic_class_id">
                        <option value="">All classes</option>

                        @foreach ($academicClasses as $academicClass)
                            <option
                                value="{{ $academicClass->id }}"
                                @selected((string) $academicClassId === (string) $academicClass->id)
                            >
                                {{ $academicClass->name }}
                                ({{ $academicClass->academic_year }})
                                @if ($academicClass->trashed())
                                    — Archived
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendance-subject">Subject</label>
                    <select id="attendance-subject" name="subject_id">
                        <option value="">All subjects</option>

                        @foreach ($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                @selected((string) $subjectId === (string) $subject->id)
                            >
                                {{ $subject->name }} ({{ $subject->code }})
                                @if ($subject->trashed())
                                    — Archived
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendance-from">Attendance from</label>
                    <input
                        type="date"
                        id="attendance-from"
                        name="attendance_from"
                        value="{{ $attendanceFrom }}"
                    >
                </div>

                <div>
                    <label for="attendance-to">Attendance to</label>
                    <input
                        type="date"
                        id="attendance-to"
                        name="attendance_to"
                        value="{{ $attendanceTo }}"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Apply Filters
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ route('attendance-sessions.index') }}"
                        class="students-search__clear"
                    >
                        Clear Filters
                    </a>
                @endif
            </form>

            @if ($errors->any())
                <div role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="students-page__summary">

            <p>
                {{ $hasFilters ? 'Matching attendance sessions:' : 'Total attendance sessions:' }}
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

                @if ($hasFilters)
                    @if ($search !== '')
                        <p>No sessions matched your search. Try another search term.</p>
                    @else
                        <p>No sessions matched your filters. Try adjusting your criteria.</p>
                    @endif

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