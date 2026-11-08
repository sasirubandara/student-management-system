<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Enrollments | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Student Enrollments</h1>
                <p>View and manage student class enrollments.</p>
            </div>

            <div class="students-page__header-actions">

                @can('create', \App\Models\StudentEnrollment::class)
                    <a href="{{ route('student-enrollments.create') }}"
                       class="students-page__button">
                        Create Enrollment
                    </a>
                @endcan

            </div>

        </header>

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search form -->
        <section class="students-search-section">

            <form action="{{ route('student-enrollments.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="enrollment-search" class="sr-only">
                        Search enrollments
                    </label>

                    <input
                        type="search"
                        id="enrollment-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by student number, name, class or status..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('student-enrollments.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Enrollment summary -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching enrollments:' : 'Total enrollments:' }}

                <strong>{{ $enrollments->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- Enrollment list -->
        @if ($enrollments->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')

                    <h2>No Matching Enrollments</h2>

                    <p>
                        No enrollments matched your search.
                        Try another student, class or status.
                    </p>

                    <a href="{{ route('student-enrollments.index') }}"
                       class="students-page__button">
                        View All Enrollments
                    </a>

                @else

                    <h2>No Enrollments Found</h2>

                    <p>
                        There are currently no student enrollments.
                    </p>

                    @can('create', \App\Models\StudentEnrollment::class)
                        <a href="{{ route('student-enrollments.create') }}"
                           class="students-page__button">
                            Create First Enrollment
                        </a>
                    @endcan

                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Academic Class</th>
                            <th>Academic Year</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($enrollments as $enrollment)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $enrollment->student?->student_number ?? 'Unavailable' }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($enrollment->student)
                                        {{ $enrollment->student->first_name }}
                                        {{ $enrollment->student->last_name }}
                                    @else
                                        Unavailable
                                    @endif
                                </td>

                                <td>
                                    {{ $enrollment->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $enrollment->academicClass?->academic_year ?? '—' }}
                                </td>

                                <td>
                                    {{ $enrollment->enrollment_date->format('d F Y') }}
                                </td>

                                <td>
                                    <span class="students-status
                                        {{ $enrollment->status === \App\Models\StudentEnrollment::STATUS_ACTIVE
                                            ? 'students-status--active'
                                            : 'students-status--other' }}">

                                        {{ ucfirst($enrollment->status) }}

                                    </span>
                                </td>

                                <td>

                                    <a href="{{ route('student-enrollments.show', $enrollment) }}"
                                       class="students-table__view">
                                        View
                                    </a>

                                    @can('update', $enrollment)
                                        <a href="{{ route('student-enrollments.edit', $enrollment) }}"
                                           class="students-table__view">
                                            Edit
                                        </a>
                                    @endcan

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="students-page__pagination">
                {{ $enrollments->links() }}
            </div>

        @endif

    </main>

</body>
</html>