<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Academic Timetables | Student Management System
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Academic Timetables</h1>
                <p>
                    View recurring weekly lessons by academic term,
                    class, or teacher.
                </p>
            </div>

            @can('create', \App\Models\TimetableEntry::class)
                <div class="students-page__header-actions">
                    <a
                        href="{{ route('timetables.create') }}"
                        class="students-page__button"
                    >
                        Add Timetable Entry
                    </a>
                </div>
            @endcan
        </header>

        @if (session('success'))
            <div
                class="student-edit-form__success"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        <section class="students-search-section">

            <form action="{{ route('timetables.index') }}"
                  method="GET">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="academic_term_id">
                            Academic Term
                        </label>

                        <select id="academic_term_id"
                                name="academic_term_id">

                            <option value="">
                                All Terms
                            </option>

                            @foreach ($terms as $term)
                                <option
                                    value="{{ $term->id }}"
                                    @selected($termId == $term->id)
                                >
                                    {{ $term->academicYear?->name }}
                                    — {{ $term->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="academic_class_id">
                            Academic Class
                        </label>

                        <select id="academic_class_id"
                                name="academic_class_id">

                            <option value="">
                                All Classes
                            </option>

                            @foreach ($academicClasses as $class)
                                <option
                                    value="{{ $class->id }}"
                                    @selected($classId == $class->id)
                                >
                                    {{ $class->name }}
                                    ({{ $class->code }})
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="teacher_id">
                            Teacher
                        </label>

                        <select id="teacher_id"
                                name="teacher_id">

                            <option value="">
                                All Teachers
                            </option>

                            @foreach ($teachers as $teacher)
                                <option
                                    value="{{ $teacher->id }}"
                                    @selected($teacherId == $teacher->id)
                                >
                                    {{ $teacher->teacher_number }}
                                    — {{ $teacher->first_name }}
                                    {{ $teacher->last_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                </div>

                <button type="submit"
                        class="students-page__button">
                    Apply Filters
                </button>

                <a href="{{ route('timetables.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Clear Filters
                </a>

            </form>

        </section>

        <section class="students-page__summary">
            <p>
                Total timetable entries:
                <strong>{{ $entries->total() }}</strong>
            </p>
        </section>

        @if ($entries->isEmpty())

            <p>
                No timetable entries match the selected filters.
            </p>

        @else

            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Day</th>
                            <th scope="col">Time</th>
                            <th scope="col">Academic Term</th>
                            <th scope="col">Class</th>
                            <th scope="col">Subject</th>
                            <th scope="col">Teacher</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $weekdays = [
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                                7 => 'Sunday',
                            ];
                        @endphp

                        @foreach ($entries as $entry)
                            <tr>
                                <td>
                                    {{ $weekdays[$entry->day_of_week] ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ substr($entry->start_time, 0, 5) }}
                                    –
                                    {{ substr($entry->end_time, 0, 5) }}
                                </td>

                                <td>
                                    {{ $entry->academicTerm?->academicYear?->name }}
                                    —
                                    {{ $entry->academicTerm?->name }}
                                </td>

                                <td>
                                    {{ $entry->teachingAssignment?->academicClass?->name }}
                                </td>

                                <td>
                                    {{ $entry->teachingAssignment?->subject?->name }}
                                </td>

                                <td>
                                    {{ $entry->teachingAssignment?->teacher?->first_name }}
                                    {{ $entry->teachingAssignment?->teacher?->last_name }}
                                </td>

                                <td>
                                    <a
                                        href="{{ route('timetables.show', $entry) }}"
                                        class="students-page__button students-page__button--secondary"
                                    >
                                        View
                                    </a>

                                    @can('update', $entry)
                                        <a
                                            href="{{ route('timetables.edit', $entry) }}"
                                            class="students-page__button"
                                        >
                                            Edit
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                {{ $entries->links() }}
            </div>

        @endif

    </main>

</body>
</html>