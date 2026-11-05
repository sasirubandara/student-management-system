<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Student Management</h1>
                <p>View and manage registered students.</p>
            </div>

            <a href="{{ route('students.create') }}"
               class="students-page__button">
                + Register New Student
            </a>
        </header>
        @if (session('success'))
    <div class="student-edit-form__success">
        {{ session('success') }}
    </div>
        @endif
        <!-- Search form -->
        <section class="students-search-section">

            <form action="{{ route('students.index') }}"
                  method="GET"
                  class="students-search">

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

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('students.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Student count -->
        <section class="students-page__summary">
            <p>
                {{ $search !== '' ? 'Matching students:' : 'Total registered students:' }}
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

                @if ($search !== '')
                    <h2>No Matching Students</h2>
                    <p>
                        No students matched your search.
                        Try another student number, name or email.
                    </p>

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