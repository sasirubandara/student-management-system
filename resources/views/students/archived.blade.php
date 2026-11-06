<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Archived Students | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">
            <div>
                <h1>Archived Students</h1>
                <p>View and restore previously archived student records.</p>
            </div>

            <div class="students-page__header-actions">

                <a href="{{ route('students.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Students
                </a>

            </div>
        </header>

        <!-- Archived student count -->
        <section class="students-page__summary">
            <p>
                Total archived students:
                <strong>{{ $students->total() }}</strong>
            </p>
        </section>

        <!-- Archived students table or empty state -->
        @if ($students->isEmpty())

            <section class="students-page__empty">
                <h2>No Archived Students</h2>

                <p>
                    There are currently no archived student records.
                </p>

                <a href="{{ route('students.index') }}"
                   class="students-page__button">
                    View All Students
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
                            <th>Archived On</th>
                            <th>Action</th>
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
                                    {{ $student->deleted_at?->format('d M Y, h:i A') ?? '—' }}
                                </td>

                                <td>
                                    <form
                                        action="{{ route('students.restore', $student) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to restore this student?');"
                                    >
                                        @csrf

                                        <button type="submit"
                                                class="student-restore-button">
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
                {{ $students->links() }}
            </div>

        @endif

    </main>

</body>
</html>