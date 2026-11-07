<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Academic Class | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Create Academic Class</h1>
                <p>Register a new academic class in the system.</p>
            </div>
        </header>

        <section class="students-page__form-wrapper">

            <form action="{{ route('academic-classes.store') }}"
                  method="POST"
                  class="student-edit-form">

                @csrf

                <!-- Class name -->
                <div class="student-edit-form__field">

                    <label for="name">Class Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="100"
                        placeholder="Example: Grade 10 - A"
                        required
                    >

                    @error('name')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Class code -->
                <div class="student-edit-form__field">

                    <label for="code">Class Code</label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ old('code') }}"
                        maxlength="20"
                        placeholder="Example: CLS-1001"
                        required
                    >

                    @error('code')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Grade level -->
                <div class="student-edit-form__field">

                    <label for="grade_level">Grade Level</label>

                    <select id="grade_level" name="grade_level" required>

                        <option value="">Select Grade Level</option>

                        @for ($grade = 1; $grade <= 13; $grade++)
                            <option
                                value="{{ $grade }}"
                                @selected(old('grade_level') == $grade)
                            >
                                Grade {{ $grade }}
                            </option>
                        @endfor

                    </select>

                    @error('grade_level')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Academic year -->
                <div class="student-edit-form__field">

                    <label for="academic_year">Academic Year</label>

                    <input
                        type="number"
                        id="academic_year"
                        name="academic_year"
                        value="{{ old('academic_year', now()->year) }}"
                        min="2000"
                        max="2100"
                        required
                    >

                    @error('academic_year')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Description -->
                <div class="student-edit-form__field">

                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="5000"
                        placeholder="Enter additional class information..."
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Status -->
                <div class="student-edit-form__field">

                    <label for="status">Class Status</label>

                    <select id="status" name="status" required>

                        <option
                            value="active"
                            @selected(old('status', 'active') === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            @selected(old('status') === 'inactive')
                        >
                            Inactive
                        </option>

                    </select>

                    @error('status')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Form actions -->
                <div class="student-edit-form__actions">

                    <button type="submit"
                            class="students-page__button">
                        Register Class
                    </button>

                    <a href="{{ route('academic-classes.index') }}"
                       class="students-search__clear">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>