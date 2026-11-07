<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Subject | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Edit Subject</h1>
                <p>Update information for {{ $subject->name }}.</p>
            </div>
        </header>

        <section class="students-page__form-wrapper">

            <form
                action="{{ route('subjects.update', $subject) }}"
                method="POST"
                class="student-edit-form"
            >

                @csrf
                @method('PUT')

                <!-- Subject name -->
                <div class="student-edit-form__field">

                    <label for="name">Subject Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $subject->name) }}"
                        maxlength="100"
                        required
                    >

                    @error('name')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Subject code -->
                <div class="student-edit-form__field">

                    <label for="code">Subject Code</label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ old('code', $subject->code) }}"
                        maxlength="20"
                        required
                    >

                    @error('code')
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
                    >{{ old('description', $subject->description) }}</textarea>

                    @error('description')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <!-- Subject status -->
                <div class="student-edit-form__field">

                    <label for="status">Subject Status</label>

                    <select id="status" name="status" required>

                        <option
                            value="active"
                            @selected(old('status', $subject->status) === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            @selected(old('status', $subject->status) === 'inactive')
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

                    <button type="submit" class="students-page__button">
                        Save Changes
                    </button>

                    <a href="{{ route('subjects.show', $subject) }}"
                       class="students-search__clear">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>