<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teaching Assignments | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Teaching Assignment Management</h1>
                <p>View and manage teachers assigned to academic classes and subjects.</p>
            </div>

            @can('create', \App\Models\TeachingAssignment::class)
                <div class="students-page__header-actions">

                    <a href="{{ route('teaching-assignments.create') }}"
                       class="students-page__button">
                        Add Teaching Assignment
                    </a>

                </div>
            @endcan

        </header>

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search form -->
        <section class="students-search-section">

            <form action="{{ route('teaching-assignments.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="assignment-search" class="sr-only">
                        Search teaching assignments
                    </label>

                    <input
                        type="search"
                        id="assignment-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by teacher, class or subject..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('teaching-assignments.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- Assignment summary -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching assignments:' : 'Total teaching assignments:' }}

                <strong>{{ $assignments->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- Assignment listing -->
        @if ($assignments->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')

                    <h2>No Matching Assignments</h2>

                    <p>
                        No teaching assignments matched your search.
                        Try another teacher, class or subject.
                    </p>

                    <a href="{{ route('teaching-assignments.index') }}"
                       class="students-page__button">
                        View All Assignments
                    </a>

                @else

                    <h2>No Teaching Assignments Found</h2>

                    <p>
                        There are currently no teaching assignments registered.
                    </p>

                    @can('create', \App\Models\TeachingAssignment::class)
                        <a href="{{ route('teaching-assignments.create') }}"
                           class="students-page__button">
                            Add First Assignment
                        </a>
                    @endcan

                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Academic Class</th>
                            <th>Subject</th>
                            <th>Assigned On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($assignments as $assignment)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $assignment->teacher->teacher_number }}
                                    </strong>

                                    <br>

                                    {{ $assignment->teacher->first_name }}
                                    {{ $assignment->teacher->last_name }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $assignment->academicClass->name }}
                                    </strong>

                                    <br>

                                    {{ $assignment->academicClass->code }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $assignment->subject->name }}
                                    </strong>

                                    <br>

                                    {{ $assignment->subject->code }}
                                </td>

                                <td>
                                    {{ $assignment->created_at->format('d F Y') }}
                                </td>

                                <td>

                                    <a href="{{ route('teaching-assignments.show', $assignment) }}"
                                       class="students-table__view">
                                        View
                                    </a>

                                    @can('update', $assignment)

                                        <a href="{{ route('teaching-assignments.edit', $assignment) }}"
                                           class="students-table__view">
                                            Edit
                                        </a>

                                    @endcan

                                    @can('delete', $assignment)

                                        <form
                                            action="{{ route('teaching-assignments.destroy', $assignment) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to remove this teaching assignment?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="students-table__view">
                                                Remove
                                            </button>

                                        </form>

                                    @endcan

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="students-page__pagination">
                {{ $assignments->links() }}
            </div>

        @endif

    </main>

</body>
</html>