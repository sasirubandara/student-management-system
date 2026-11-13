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

        <section class="students-search-section">
            <form
                method="GET"
                action="{{ route('examinations.index') }}"
                class="students-search advanced-filters"
            >
                <div class="students-search__input-wrapper">
                    <label for="examination-search" class="sr-only">
                        Search examinations
                    </label>

                    <input
                        type="search"
                        id="examination-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search examinations, teachers, classes or subjects..."
                        maxlength="255"
                    >
                </div>

                <div>
                    <label for="examination-type">Examination type</label>

                    <select id="examination-type" name="exam_type">
                        <option value="">All types</option>

                        @foreach ($examTypes as $type)
                            <option
                                value="{{ $type }}"
                                @selected($examType === $type)
                            >
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="examination-teacher">Teacher</label>

                    <select id="examination-teacher" name="teacher_id">
                        <option value="">All teachers</option>

                        @foreach ($teachers as $teacher)
                            <option
                                value="{{ $teacher->id }}"
                                @selected((string) $teacherId === (string) $teacher->id)
                            >
                                {{ $teacher->teacher_number }}
                                — {{ $teacher->first_name }} {{ $teacher->last_name }}
                                @if ($teacher->trashed())
                                    (Archived)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="examination-class">Academic class</label>

                    <select id="examination-class" name="academic_class_id">
                        <option value="">All classes</option>

                        @foreach ($academicClasses as $academicClass)
                            <option
                                value="{{ $academicClass->id }}"
                                @selected((string) $academicClassId === (string) $academicClass->id)
                            >
                                {{ $academicClass->name }}
                                ({{ $academicClass->academic_year }})
                                @if ($academicClass->trashed())
                                    — Archived
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="examination-subject">Subject</label>

                    <select id="examination-subject" name="subject_id">
                        <option value="">All subjects</option>

                        @foreach ($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                @selected((string) $subjectId === (string) $subject->id)
                            >
                                {{ $subject->name }} ({{ $subject->code }})
                                @if ($subject->trashed())
                                    — Archived
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="examination-from">Exam date from</label>

                    <input
                        type="date"
                        id="examination-from"
                        name="exam_from"
                        value="{{ $examFrom }}"
                    >
                </div>

                <div>
                    <label for="examination-to">Exam date to</label>

                    <input
                        type="date"
                        id="examination-to"
                        name="exam_to"
                        value="{{ $examTo }}"
                    >
                </div>

                <button type="submit" class="students-page__button">
                    Apply Filters
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ route('examinations.index') }}"
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

        <section class="students-page__summary">
            <p>
                {{ $hasFilters ? 'Matching examinations:' : 'Total examinations:' }}
                <strong>{{ $examinations->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for: <strong>{{ $search }}</strong>
                </p>
            @endif
        </section>

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
                                @if ($hasFilters)
                                    No examinations matched your filters.
                                    Try adjusting your search criteria.
                                @else
                                    No examinations found.
                                @endif
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