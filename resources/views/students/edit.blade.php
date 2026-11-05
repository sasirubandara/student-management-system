<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Student | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">
            <div>
                <h1>Edit Student</h1>
                <p>Update the registered student information.</p>
            </div>

            <a href="{{ route('students.show', $student) }}"
               class="students-page__button students-page__button--secondary">
                Cancel
            </a>
        </header>

        <!-- Editing form -->
        <section class="student-edit-form">

            <form action="{{ route('students.update', $student) }}"
                  method="POST">

                @csrf
                @method('PUT')

                <!-- Validation errors -->
                @if ($errors->any())
                    <div class="student-edit-form__errors">
                        <strong>Please correct the following errors:</strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Personal information -->
                <div class="student-edit-form__section">

                    <h2>Personal Information</h2>

                    <div class="student-edit-form__grid">

                        <div class="student-edit-form__field">
                            <label for="first_name">First Name</label>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="{{ old('first_name', $student->first_name) }}"
                                maxlength="100"
                                required
                            >

                            @error('first_name')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="student-edit-form__field">
                            <label for="last_name">Last Name</label>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="{{ old('last_name', $student->last_name) }}"
                                maxlength="100"
                                required
                            >

                            @error('last_name')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="student-edit-form__field">
                            <label for="date_of_birth">Date of Birth</label>

                            <input
                                type="date"
                                id="date_of_birth"
                                name="date_of_birth"
                                value="{{ old('date_of_birth', $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('Y-m-d') : '') }}"
                            >

                            @error('date_of_birth')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                    </div>

                </div>

                <!-- Contact information -->
                <div class="student-edit-form__section">

                    <h2>Contact Information</h2>

                    <div class="student-edit-form__grid">

                        <div class="student-edit-form__field">
                            <label for="email">Email Address</label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $student->email) }}"
                                maxlength="255"
                            >

                            @error('email')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="student-edit-form__field">
                            <label for="phone">Phone Number</label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ old('phone', $student->phone) }}"
                                maxlength="30"
                            >

                            @error('phone')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                    </div>

                </div>

                <!-- Academic information -->
                <div class="student-edit-form__section">

                    <h2>Academic Information</h2>

                    <div class="student-edit-form__grid">

                        <div class="student-edit-form__field">
                            <label for="student_number">
                                Student Number
                            </label>

                            <input
                                type="text"
                                id="student_number"
                                name="student_number"
                                value="{{ old('student_number', $student->student_number) }}"
                                maxlength="20"
                                required
                            >

                            @error('student_number')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="student-edit-form__field">
                            <label for="enrollment_date">
                                Enrollment Date
                            </label>

                            <input
                                type="date"
                                id="enrollment_date"
                                name="enrollment_date"
                                value="{{ old('enrollment_date', \Illuminate\Support\Carbon::parse($student->enrollment_date)->format('Y-m-d')) }}"
                                required
                            >

                            @error('enrollment_date')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="student-edit-form__field">
                            <label for="status">Student Status</label>

                            <select id="status" name="status" required>
                                <option value="active"
                                    @selected(old('status', $student->status) === 'active')>
                                    Active
                                </option>

                                <option value="inactive"
                                    @selected(old('status', $student->status) === 'inactive')>
                                    Inactive
                                </option>
                            </select>

                            @error('status')
                                <span class="student-edit-form__error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                    </div>

                </div>

                <!-- Form actions -->
                <div class="student-edit-form__actions">

                    <button type="submit"
                            class="students-page__button">
                        Update Student
                    </button>

                    <a href="{{ route('students.show', $student) }}"
                       class="students-page__button students-page__button--secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>