<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teachers | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Teacher Management</h1>
                <p>View and manage registered teachers.</p>
            </div>

            <div class="students-page__header-actions">

                @can('viewArchived', \App\Models\Teacher::class)
                    <a href="{{ route('teachers.archived') }}"
                       class="students-page__button students-page__button--secondary">
                        Archived Teachers
                    </a>
                @endcan

                @can('create', \App\Models\Teacher::class)
                    <a href="{{ route('teachers.create') }}"
                       class="students-page__button">
                        Register Teacher
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

            <form action="{{ route('teachers.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="teacher-search" class="sr-only">
                        Search teachers
                    </label>

                    <input
                        type="search"
                        id="teacher-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by teacher number, name or email..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('teachers.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Teacher summary -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching teachers:' : 'Total registered teachers:' }}

                <strong>{{ $teachers->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- Teacher listing -->
        @if ($teachers->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')

                    <h2>No Matching Teachers</h2>

                    <p>
                        No teachers matched your search.
                        Try another teacher number, name or email.
                    </p>

                    <a href="{{ route('teachers.index') }}"
                       class="students-page__button">
                        View All Teachers
                    </a>

                @else

                    <h2>No Teachers Found</h2>

                    <p>
                        There are currently no registered teachers.
                    </p>

                    @can('create', \App\Models\Teacher::class)
                        <a href="{{ route('teachers.create') }}"
                           class="students-page__button">
                            Register First Teacher
                        </a>
                    @endcan

                @endif

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
                            <th>Status</th>
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
                                    <span class="students-status
                                        {{ $teacher->status === \App\Models\Teacher::STATUS_ACTIVE
                                            ? 'students-status--active'
                                            : 'students-status--other' }}">

                                        {{ ucfirst($teacher->status) }}

                                    </span>
                                </td>

                                <td>

                                    <a href="{{ route('teachers.show', $teacher) }}"
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
                {{ $teachers->links() }}
            </div>

        @endif

    </main>

</body>
</html>