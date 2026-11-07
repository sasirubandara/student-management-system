<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Subject Management | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Subject Management</h1>
                <p>View and search registered subjects.</p>
            </div>

            @can('create', \App\Models\Subject::class)
                <div>
                    <a href="{{ route('subjects.create') }}"
                       class="students-page__button">
                        Create New Subject
                    </a>
                </div>
            @endcan
        </header>

        @if (session('success'))
            <div class="student-edit-form__success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search subjects -->
        <section class="students-search-section">

            <form action="{{ route('subjects.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="subject-search" class="sr-only">
                        Search subjects
                    </label>

                    <input
                        type="search"
                        id="subject-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by subject name or code..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('subjects.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Subject summary -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching subjects:' : 'Total registered subjects:' }}

                <strong>{{ $subjects->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- Subject listing -->
        @if ($subjects->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')
                    <h2>No Matching Subjects</h2>

                    <p>
                        No subjects matched your search.
                        Try another subject name or code.
                    </p>

                    <a href="{{ route('subjects.index') }}"
                       class="students-page__button">
                        View All Subjects
                    </a>
                @else
                    <h2>No Subjects Found</h2>

                    <p>
                        There are currently no registered subjects.
                    </p>

                    @can('create', \App\Models\Subject::class)
                        <a href="{{ route('subjects.create') }}"
                           class="students-page__button">
                            Register First Subject
                        </a>
                    @endcan
                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($subjects as $subject)

                            <tr>

                                <td>
                                    <strong>{{ $subject->name }}</strong>
                                </td>

                                <td>
                                    {{ $subject->code }}
                                </td>

                                <td>
                                    {{ $subject->description
                                        ? \Illuminate\Support\Str::limit($subject->description, 80)
                                        : '—' }}
                                </td>

                                <td>
                                    <span class="students-status
                                        {{ $subject->status === 'active'
                                            ? 'students-status--active'
                                            : 'students-status--other' }}">
                                        {{ ucfirst($subject->status) }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('subjects.show', $subject) }}"
                                       class="students-table__view">
                                        View
                                    </a>

                                    @can('update', $subject)
                                        <a href="{{ route('subjects.edit', $subject) }}"
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
                {{ $subjects->links() }}
            </div>

        @endif

    </main>

</body>
</html>