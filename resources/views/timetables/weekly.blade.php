<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $title }} | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

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

        <header class="students-page__header">

            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>

            <a href="{{ route('timetables.index') }}"
               class="students-page__button students-page__button--secondary">
                Back to Timetables
            </a>

        </header>

        <section class="students-search-section">

            <form action="{{ $filterRoute }}"
                  method="GET">

                <div class="form-group">
                    <label for="academic_term_id">
                        Academic Term
                    </label>

                    <select id="academic_term_id"
                            name="academic_term_id"
                            required>

                        <option value="">
                            Select Academic Term
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

                <button type="submit"
                        class="students-page__button">
                    View Weekly Timetable
                </button>

            </form>

        </section>

        @if (! $termId)

            <p>
                Select an academic term to view the weekly timetable.
            </p>

        @else

            @foreach ($weekdays as $dayNumber => $dayName)

                <section class="students-search-section">

                    <h2>{{ $dayName }}</h2>

                    @if (! $entriesByDay->has($dayNumber))

                        <p>No lessons scheduled.</p>

                    @else

                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">Time</th>
                                        <th scope="col">Subject</th>

                                        @if ($showClass)
                                            <th scope="col">
                                                Academic Class
                                            </th>
                                        @endif

                                        @if ($showTeacher)
                                            <th scope="col">
                                                Teacher
                                            </th>
                                        @endif

                                        <th scope="col">Details</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($entriesByDay[$dayNumber] as $entry)
                                        <tr>
                                            <td>
                                                {{ substr($entry->start_time, 0, 5) }}
                                                –
                                                {{ substr($entry->end_time, 0, 5) }}
                                            </td>

                                            <td>
                                                {{ $entry->teachingAssignment?->subject?->name }}
                                            </td>

                                            @if ($showClass)
                                                <td>
                                                    {{ $entry->teachingAssignment?->academicClass?->name }}
                                                </td>
                                            @endif

                                            @if ($showTeacher)
                                                <td>
                                                    {{ $entry->teachingAssignment?->teacher?->first_name }}
                                                    {{ $entry->teachingAssignment?->teacher?->last_name }}
                                                </td>
                                            @endif

                                            <td>
                                                <a
                                                    href="{{ route('timetables.show', $entry) }}"
                                                    class="students-page__button students-page__button--secondary"
                                                >
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif

                </section>

            @endforeach

        @endif

    </main>

</body>
</html>