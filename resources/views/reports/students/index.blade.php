<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Academic Reports</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="reports-page">

        <header class="report-header">
            <div>
                <h1>Student Academic Reports</h1>
                <p>
                    Select a student to view their enrollment,
                    attendance, examination and grade history.
                </p>
            </div>
        </header>

        <section class="report-panel">

            <form
                action="{{ route('reports.students.index') }}"
                method="GET"
                class="report-search-form"
            >
                <div>
                    <label for="search">Search students</label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Student number, name or email"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('reports.students.index') }}">
                        Clear search
                    </a>
                @endif
            </form>

            @if ($students->isEmpty())

                <div class="report-empty">
                    <h2>No students found</h2>

                    <p>
                        No students match your search.
                        Try a different search term.
                    </p>
                </div>

            @else

                <div class="report-table-wrapper">

                    <table class="students-table">

                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($students as $student)

                                <tr>
                                    <td>
                                        {{ $student->student_number }}
                                    </td>

                                    <td>
                                        {{ $student->first_name }}
                                        {{ $student->last_name }}
                                    </td>

                                    <td>
                                        {{ $student->email ?? '—' }}
                                    </td>

                                    <td>
                                        {{ $student->trashed() ? 'Archived' : ucfirst($student->status) }}
                                    </td>

                                    <td>
                                        <a
                                            href="{{ route('reports.students.show', $student) }}"
                                            class="report-view-link"
                                        >
                                            View Academic Report
                                        </a>
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="report-pagination">
                    {{ $students->links() }}
                </div>

            @endif

        </section>

    </main>

</body>
</html>