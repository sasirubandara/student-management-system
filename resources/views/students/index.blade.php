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

        <!-- Page header -->
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

        <!-- Student count -->
        <section class="students-page__summary">
            <p>
                Total registered students:
                <strong>{{ $students->total() }}</strong>
            </p>
        </section>

        <!-- Student table or empty state -->
        @if ($students->isEmpty())

            <section class="students-page__empty">
                <h2>No Students Found</h2>

                <p>
                    There are currently no registered students.
                    Register a student to get started.
                </p>

                <a href="{{ route('students.create') }}"
                   class="students-page__button">
                    Register First Student
                </a>
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