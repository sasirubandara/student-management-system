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

        <!-- Search and filter form -->
        <section class="students-search-section">
            <form
                action="{{ route('teachers.index') }}"
                method="GET"
                class="students-search advanced-filters"
            >
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

                <div>
                    <label for="teacher-status">Employment status</label>
                    <select id="teacher-status" name="status">
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
                    <label for="teacher-employed-from">
                        Employed from
                    </label>

                    <input
                        type="date"
                        id="teacher-employed-from"
                        name="employed_from"
                        value="{{ $employedFrom }}"
                    >
                </div>

                <div>
                    <label for="teacher-employed-to">
                        Employed to
                    </label>

                    <input
                        type="date"
                        id="teacher-employed-to"
                        name="employed_to"
                        value="{{ $employedTo }}"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Apply Filters
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ route('teachers.index') }}"
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

        <!-- Teacher summary -->
        <section class="students-page__summary">

            <p>
                {{ $hasFilters ? 'Matching teachers:' : 'Total registered teachers:' }}

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

                @if ($hasFilters)

                    <h2>No Matching Teachers</h2>

                    @if ($search !== '')
                        <p>
                            No teachers matched your search.
                            Try another teacher number, name or email.
                        </p>
                    @else
                        <p>
                            No teachers matched your filters.
                            Try adjusting your criteria.
                        </p>
                    @endif

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