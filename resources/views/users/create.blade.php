<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Staff Account | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>Create Staff Account</h1>
                <p>Register a new staff member in the system.</p>
            </div>
        </header>

        <section class="students-page__form-wrapper">

            <form action="{{ route('users.store') }}"
                  method="POST"
                  class="student-edit-form">

                @csrf

                <div class="student-edit-form__field">

                    <label for="name">Full Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="255"
                        required
                    >

                    @error('name')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <div class="student-edit-form__field">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        maxlength="255"
                        required
                    >

                    @error('email')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <div class="student-edit-form__field">

                    <label for="password">Initial Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >

                    <p>Use a password with at least 12 characters.</p>

                    @error('password')
                        <p class="student-edit-form__error">{{ $message }}</p>
                    @enderror

                </div>

                <div class="student-edit-form__field">

                    <label for="password_confirmation">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >

                </div>

                <div class="student-edit-form__actions">

                    <button type="submit"
                            class="students-page__button">
                        Create Staff Account
                    </button>

                    <a href="{{ route('users.index') }}"
                       class="students-search__clear">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>