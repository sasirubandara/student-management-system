<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Academic Class | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Edit Academic Class</h1>
                <p>Update the information for {{ $academicClass->name }}.</p>
            </div>
        </header>

        <section class="students-page__form-wrapper">

            <form
                action="{{ route('academic-classes.update', $academicClass) }}"
                method="POST"
                class="student-edit-form"
            >

                @csrf
                @method('PUT')

                <div class="student-edit-form__field">
                    <label for="name">Class Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $academicClass->name) }}"
                        maxlength="100"
                        required
                    >

                    @error('name')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="student-edit-form__field">
                    <label for="code">Class Code</label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ old('code', $academicClass->code) }}"
                        maxlength="20"
                        required
                    >

                    @error('code')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="student-edit-form__field">
                    <label for="grade_level">Grade Level</label>

                    <select id="grade_level" name="grade_level" required>
                        <option value="">Select Grade Level</option>

                        @for ($grade = 1; $grade <= 13; $grade++)
                            <option
                                value="{{ $grade }}"
                                @selected(old('grade_level', $academicClass->grade_level) == $grade)
                            >
                                Grade {{ $grade }}
                            </option>
                        @endfor
                    </select>

                    @error('grade_level')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="student-edit-form__field">

                    <label for="academic_year">Academic Year</label>

                    @php
                        $selectedYear = old(
                            'academic_year',
                            $academicClass->academic_year
                        );

                        $isHistoricalYear = ! $academicYears->contains(
                            'year',
                            (int) $academicClass->academic_year
                        );
                    @endphp

                    <select
                        id="academic_year"
                        name="academic_year"
                        required
                    >
                        <option value="">Select Academic Year</option>

                        @if ($isHistoricalYear)
                            <option
                                value="{{ $academicClass->academic_year }}"
                                @selected(
                                    (string) $selectedYear ===
                                    (string) $academicClass->academic_year
                                )
                            >
                                {{ $academicClass->academic_year }}
                                (Historical — Not Registered)
                            </option>
                        @endif

                        @foreach ($academicYears as $academicYear)
                            <option
                                value="{{ $academicYear->year }}"
                                @selected(
                                    (string) $selectedYear ===
                                    (string) $academicYear->year
                                )
                            >
                                {{ $academicYear->year }} — {{ $academicYear->name }}
                            </option>
                        @endforeach

                    </select>

                    @if ($isHistoricalYear)
                        <p>
                            This class uses an academic year that is not
                            registered in the Academic Calendar.
                            You may retain it or select a registered year.
                        </p>
                    @endif

                    @error('academic_year')
                        <p class="student-edit-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="student-edit-form__field">
                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="5000"
                    >{{ old('description', $academicClass->description) }}</textarea>

                    @error('description')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="student-edit-form__field">
                    <label for="status">Class Status</label>

                    <select id="status" name="status" required>
                        <option value="active"
                            @selected(old('status', $academicClass->status) === 'active')>
                            Active
                        </option>

                        <option value="inactive"
                            @selected(old('status', $academicClass->status) === 'inactive')>
                            Inactive
                        </option>
                    </select>

                    @error('status')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="student-edit-form__actions">

                    <button type="submit" class="students-page__button">
                        Save Changes
                    </button>

                    <a href="{{ route('academic-classes.show', $academicClass) }}"
                       class="students-search__clear">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>