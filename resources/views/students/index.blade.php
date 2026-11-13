<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">
        <header class="students-page__header">
    <div>
        <h1>Student Management</h1>
        <p>View and manage registered students.</p>
    </div>

    <div class="students-page__header-actions">

    @can('create', \App\Models\Student::class)
        <a
            href="{{ route('students.import.create') }}"
            class="students-page__button students-page__button--secondary"
        >
            Import CSV
        </a>
    @endcan

    @can('viewAny', \App\Models\Student::class)
        <a
            href="{{ route('students.export', ['search' => $search]) }}"
            class="students-page__button"
        >
            Export CSV
        </a>
    @endcan

    @can('viewArchived', \App\Models\Student::class)

    <a href="{{ route('students.archived') }}"
       class="students-page__button students-page__button--secondary">
        Archived Students
    </a>

    @endcan

    </div>
        </header>

        @if (session('success'))
    <div class="student-edit-form__success">
        {{ session('success') }}
    </div>
        @endif

        <!-- Search and filter form -->
        <section class="students-search-section">
            <form
                action="{{ route('students.index') }}"
                method="GET"
                class="students-search advanced-filters"
            >
                <div class="students-search__input-wrapper">
                    <label for="student-search" class="sr-only">
                        Search students
                    </label>

                    <input
                        type="search"
                        id="student-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by student number, name or email..."
                        maxlength="255"
                    >
                </div>

                <div>
                    <label for="student-status">Status</label>
                    <select id="student-status" name="status">
                        <option value="">All statuses</option>
                        <option
                            value="active"
                            @selected($status === 'active')
                        >
                            Active
                        </option>
                        <option
                            value="inactive"
                            @selected($status === 'inactive')
                        >
                            Inactive
                        </option>
                    </select>
                </div>

                <div>
                    <label for="student-enrolled-from">
                        Enrolled from
                    </label>
                    <input
                        type="date"
                        id="student-enrolled-from"
                        name="enrolled_from"
                        value="{{ $enrolledFrom }}"
                    >
                </div>

                <div>
                    <label for="student-enrolled-to">
                        Enrolled to
                    </label>
                    <input
                        type="date"
                        id="student-enrolled-to"
                        name="enrolled_to"
                        value="{{ $enrolledTo }}"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Apply Filters
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ route('students.index') }}"
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

        <!-- Student count -->
        <section class="students-page__summary">
            <p>
                {{ $hasFilters ? 'Matching students:' : 'Total registered students:' }}
                <strong>{{ $students->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif
        </section>

        <!-- Student list -->
        @if ($students->isEmpty())

            <section class="students-page__empty">

                @if ($hasFilters)
                    <h2>No Matching Students</h2>
                    @if ($search !== '')
                        <p>
                            No students matched your search.
                            Try another student number, name or email.
                        </p>
                    @else
                        <p>
                            No students matched your filters.
                            Try adjusting your criteria.
                        </p>
                    @endif

                    <a href="{{ route('students.index') }}"
                       class="students-page__button">
                        View All Students
                    </a>
                @else
                    <h2>No Students Found</h2>
                    <p>
                        There are currently no registered students.
                        Register a student to get started.
                    </p>

                    <a href="{{ route('students.create') }}"
                       class="students-page__button">
                        Register First Student
                    </a>
                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($students as $student)

                            <tr>
                                <td>
                                    <strong>
                                        {{ $student->student_number }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $student->first_name }}
                                    {{ $student->last_name }}
                                </td>

                                <td>
                                    {{ $student->email ?? '—' }}
                                </td>

                                <td>
                                    {{ $student->phone ?? '—' }}
                                </td>

                                <td>
                                    {{ $student->enrollment_date }}
                                </td>

                                <td>
                                    <span class="students-status
                                        {{ strtolower($student->status) === 'active'
                                            ? 'students-status--active'
                                            : 'students-status--other' }}">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('students.show', $student) }}"
                                       class="students-table__view">
                                        View
                                    </a>
                                </td>
                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="students-page__pagination">
                {{ $students->links() }}
            </div>

        @endif

    </main>

</body>
</html>