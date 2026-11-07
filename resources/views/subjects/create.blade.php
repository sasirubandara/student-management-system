<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Subject | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Create Subject</h1>
                <p>Register a new subject in the system.</p>
            </div>
        </header>

        <section class="students-page__form-wrapper">

            <form action="{{ route('subjects.store') }}"
                  method="POST"
                  class="student-edit-form">

                @csrf

                <!-- Subject name -->
                <div class="student-edit-form__field">

                    <label for="name">Subject Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="100"
                        placeholder="Example: Mathematics"
                        required
                    >

                    @error('name')
                        <p class="student-edit-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <!-- Subject code -->
                <div class="student-edit-form__field">

                    <label for="code">Subject Code</label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ old('code') }}"
                        maxlength="20"
                        placeholder="Example: SUB-001"
                        required
                    >

                    @error('code')
                        <p class="student-edit-form__error">
                            {{ $message }}
                        </p>
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
                        placeholder="Enter additional subject information..."
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <p class="student-edit-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <!-- Subject status -->
                <div class="student-edit-form__field">

                    <label for="status">Subject Status</label>

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
                        <p class="student-edit-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <!-- Form actions -->
                <div class="student-edit-form__actions">

                    <button type="submit"
                            class="students-page__button">
                        Register Subject
                    </button>

                    <a href="{{ route('subjects.index') }}"
                       class="students-search__clear">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>