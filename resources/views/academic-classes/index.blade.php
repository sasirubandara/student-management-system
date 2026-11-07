<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Academic Classes | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Academic Class Management</h1>
                <p>View and search registered academic classes.</p>
            </div>

            @can('create', \App\Models\AcademicClass::class)
                <div>
                    <a href="{{ route('academic-classes.create') }}"
                       class="students-page__button">
                        Create New Class
                    </a>
                </div>
            @endcan
        </header>

        @if (session('success'))
            <div class="student-edit-form__success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search classes -->
        <section class="students-search-section">

            <form action="{{ route('academic-classes.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="class-search" class="sr-only">
                        Search academic classes
                    </label>

                    <input
                        type="search"
                        id="class-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by class name or class code..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('academic-classes.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Class summary -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching classes:' : 'Total academic classes:' }}

                <strong>{{ $academicClasses->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- Class listing -->
        @if ($academicClasses->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')
                    <h2>No Matching Classes</h2>

                    <p>
                        No academic classes matched your search.
                        Try another class name or code.
                    </p>

                    <a href="{{ route('academic-classes.index') }}"
                       class="students-page__button">
                        View All Classes
                    </a>
                @else
                    <h2>No Academic Classes Found</h2>

                    <p>
                        There are currently no registered academic classes.
                    </p>
                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Class Code</th>
                            <th>Grade Level</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($academicClasses as $academicClass)

                            <tr>

                                <td>
                                    <strong>{{ $academicClass->name }}</strong>
                                </td>

                                <td>
                                    {{ $academicClass->code }}
                                </td>

                                <td>
                                    Grade {{ $academicClass->grade_level }}
                                </td>

                                <td>
                                    {{ $academicClass->academic_year }}
                                </td>

                                <td>
                                    <span class="students-status
                                        {{ $academicClass->status === 'active'
                                            ? 'students-status--active'
                                            : 'students-status--other' }}">
                                        {{ ucfirst($academicClass->status) }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('academic-classes.show', $academicClass) }}"
                                       class="students-table__view">
                                        View
                                    </a>

                                    @can('update', $academicClass)
                                        <a href="{{ route('academic-classes.edit', $academicClass) }}"
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
                {{ $academicClasses->links() }}
            </div>

        @endif

    </main>

</body>
</html>