<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page">

        <header class="page-header">
            <div>
                <h1>Examinations</h1>
                <p>Manage academic examinations and student results.</p>
            </div>

            @can('create', \App\Models\Examination::class)
                <a href="{{ route('examinations.create') }}"
                   class="students-page__button">
                    Create Examination
                </a>
            @endcan
        </header>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET"
              action="{{ route('examinations.index') }}"
              class="search-form">

            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search examinations, teachers, classes, or subjects..."
                aria-label="Search examinations"
            >

            <button type="submit" class="students-page__button">
                Search
            </button>

            @if ($search !== '')
                <a href="{{ route('examinations.index') }}">
                    Clear
                </a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="students-table">

                <thead>
                    <tr>
                        <th>Examination</th>
                        <th>Teacher</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Exam Date</th>
                        <th>Type</th>
                        <th>Maximum Marks</th>
                        <th>Results</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($examinations as $examination)

                        <tr>
                            <td>
                                <strong>{{ $examination->title }}</strong>
                            </td>

                            <td>
                                <strong>
                                    {{ $examination->teachingAssignment->teacher->teacher_number }}
                                </strong>

                                <br>

                                {{ $examination->teachingAssignment->teacher->first_name }}
                                {{ $examination->teachingAssignment->teacher->last_name }}
                            </td>

                            <td>
                                {{ $examination->teachingAssignment->academicClass->name }}
                            </td>

                            <td>
                                {{ $examination->teachingAssignment->subject->name }}
                            </td>

                            <td>
                                {{ $examination->exam_date->format('d M Y') }}
                            </td>

                            <td>
                                {{ ucwords(str_replace('_', ' ', $examination->exam_type)) }}
                            </td>

                            <td>
                                {{ number_format((float) $examination->maximum_marks, 2) }}
                            </td>

                            <td>
                                {{ $examination->results_count }}
                            </td>

                            <td>
                                <div class="table-actions">

                                    <a href="{{ route('examinations.show', $examination) }}">
                                        View
                                    </a>

                                    @can('update', $examination)
                                        <a href="{{ route('examinations.edit', $examination) }}">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('delete', $examination)
                                        @if ($examination->results_count === 0)
                                            <form
                                                method="POST"
                                                action="{{ route('examinations.destroy', $examination) }}"
                                                onsubmit="return confirm('Are you sure you want to remove this examination?')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                </div>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="9">
                                No examinations found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            {{ $examinations->links() }}
        </div>

    </main>

</body>
</html>