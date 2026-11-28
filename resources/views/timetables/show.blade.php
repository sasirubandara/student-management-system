<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Timetable Entry #{{ $timetableEntry->id }}
        | Student Management System
    </title>

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

            $assignment = $timetableEntry->teachingAssignment;
        @endphp

        <header class="students-page__header">

            <div>
                <h1>Timetable Entry Details</h1>
                <p>
                    Recurring weekly lesson
                    #{{ $timetableEntry->id }}
                </p>
            </div>

            <div class="student-profile__actions">

                @can('update', $timetableEntry)
                    <a
                        href="{{ route('timetables.edit', $timetableEntry) }}"
                        class="students-page__button"
                    >
                        Edit Entry
                    </a>
                @endcan

                <a
                    href="{{ route('timetables.index') }}"
                    class="students-page__button students-page__button--secondary"
                >
                    Back to Timetables
                </a>

                @can('delete', $timetableEntry)
                    <form
                        action="{{ route('timetables.destroy', $timetableEntry) }}"
                        method="POST"
                        onsubmit="return confirm('Delete this timetable entry?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="students-page__button student-archive-button"
                        >
                            Delete Entry
                        </button>
                    </form>
                @endcan

            </div>

        </header>

        @if (session('success'))
            <div class="student-edit-form__success"
                 role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="student-profile__header">
            <div>
                <h2>Scheduled Lesson</h2>

                <dl>
                    <dt>Academic Year</dt>
                    <dd>
                        {{ $timetableEntry->academicTerm?->academicYear?->name }}
                    </dd>

                    <dt>Academic Term</dt>
                    <dd>
                        {{ $timetableEntry->academicTerm?->name }}
                    </dd>

                    <dt>Academic Class</dt>
                    <dd>
                        {{ $assignment?->academicClass?->name }}
                    </dd>

                    <dt>Subject</dt>
                    <dd>
                        {{ $assignment?->subject?->name }}
                    </dd>

                    <dt>Teacher</dt>
                    <dd>
                        {{ $assignment?->teacher?->first_name }}
                        {{ $assignment?->teacher?->last_name }}
                    </dd>

                    <dt>Weekday</dt>
                    <dd>
                        {{ $weekdays[$timetableEntry->day_of_week] ?? 'Unknown' }}
                    </dd>

                    <dt>Start Time</dt>
                    <dd>
                        {{ substr($timetableEntry->start_time, 0, 5) }}
                    </dd>

                    <dt>End Time</dt>
                    <dd>
                        {{ substr($timetableEntry->end_time, 0, 5) }}
                    </dd>
                </dl>
            </div>
        </section>

        <div class="student-profile__actions">

            @if ($assignment?->academicClass)
                <a
                    href="{{ route('timetables.classes.show', [
                        'academicClass' => $assignment->academicClass,
                        'academic_term_id' => $timetableEntry->academic_term_id,
                    ]) }}"
                    class="students-page__button"
                >
                    View Class Timetable
                </a>
            @endif

            @if ($assignment?->teacher)
                <a
                    href="{{ route('timetables.teachers.show', [
                        'teacher' => $assignment->teacher,
                        'academic_term_id' => $timetableEntry->academic_term_id,
                    ]) }}"
                    class="students-page__button students-page__button--secondary"
                >
                    View Teacher Timetable
                </a>
            @endif

        </div>

    </main>

</body>
</html>