<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Add Timetable Entry | Student Management System
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Add Timetable Entry</h1>

            <p>
                Schedule a recurring weekly lesson for an
                academic class and assigned teacher.
            </p>
        </header>

        <a href="{{ route('timetables.index') }}"
           class="students-page__button students-page__button--secondary">
            Back to Timetables
        </a>

        @if ($errors->any())
            <div class="error-summary" role="alert">
                <strong>
                    Please correct the following errors:
                </strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('timetables.store') }}"
            method="POST"
        >
            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="academic_term_id">
                        Academic Term *
                    </label>

                    <select
                        id="academic_term_id"
                        name="academic_term_id"
                        required
                    >
                        <option value="">
                            Select Academic Term
                        </option>

                        @foreach ($terms as $term)
                            <option
                                value="{{ $term->id }}"
                                @selected(old('academic_term_id') == $term->id)
                            >
                                {{ $term->academicYear?->name }}
                                — {{ $term->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('academic_term_id')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="teaching_assignment_id">
                        Teaching Assignment *
                    </label>

                    <select
                        id="teaching_assignment_id"
                        name="teaching_assignment_id"
                        required
                    >
                        <option value="">
                            Select Teaching Assignment
                        </option>

                        @foreach ($assignments as $assignment)
                            <option
                                value="{{ $assignment->id }}"
                                @selected(old('teaching_assignment_id') == $assignment->id)
                            >
                                {{ $assignment->academicClass?->name }}
                                —
                                {{ $assignment->subject?->name }}
                                —
                                {{ $assignment->teacher?->first_name }}
                                {{ $assignment->teacher?->last_name }}
                            </option>
                        @endforeach
                    </select>

                    @error('teaching_assignment_id')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="day_of_week">
                        Day of Week *
                    </label>

                    <select
                        id="day_of_week"
                        name="day_of_week"
                        required
                    >
                        <option value="">
                            Select Day
                        </option>

                        @foreach ([
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            7 => 'Sunday',
                        ] as $dayNumber => $dayName)
                            <option
                                value="{{ $dayNumber }}"
                                @selected(old('day_of_week') == $dayNumber)
                            >
                                {{ $dayName }}
                            </option>
                        @endforeach
                    </select>

                    @error('day_of_week')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="start_time">
                        Start Time *
                    </label>

                    <input
                        type="time"
                        id="start_time"
                        name="start_time"
                        value="{{ old('start_time') }}"
                        required
                    >

                    @error('start_time')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="end_time">
                        End Time *
                    </label>

                    <input
                        type="time"
                        id="end_time"
                        name="end_time"
                        value="{{ old('end_time') }}"
                        required
                    >

                    @error('end_time')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="students-page__button"
                >
                    Save Timetable Entry
                </button>

                <a
                    href="{{ route('timetables.index') }}"
                    class="students-page__button students-page__button--secondary"
                >
                    Cancel
                </a>

            </div>
        </form>

    </main>

</body>
</html>