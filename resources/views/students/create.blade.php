<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Registration</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <main class="page-container">

        <header class="page-header">
            <h1>Student Registration</h1>
            <p>Enter the student's information below.</p>
        </header>

        @if (session('success'))
            <div class="success-message" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-summary" role="alert">
                <strong>Please correct the following errors:</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('students.store') }}" method="POST">

            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="student_number">Student Number *</label>

                    <input
                        type="text"
                        id="student_number"
                        name="student_number"
                        value="{{ old('student_number') }}"
                        maxlength="20"
                        required
                    >

                    @error('student_number')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="first_name">First Name *</label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        maxlength="100"
                        autocomplete="given-name"
                        required
                    >

                    @error('first_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        maxlength="100"
                        autocomplete="family-name"
                        required
                    >

                    @error('last_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        maxlength="255"
                        autocomplete="email"
                    >

                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        maxlength="30"
                        autocomplete="tel"
                    >

                    @error('phone')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="{{ old('date_of_birth') }}"
                    >

                    @error('date_of_birth')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="enrollment_date">Enrollment Date *</label>

                    <input
                        type="date"
                        id="enrollment_date"
                        name="enrollment_date"
                        value="{{ old('enrollment_date') }}"
                        required
                    >

                    @error('enrollment_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>

            <div class="form-actions">
                <button type="submit">Register Student</button>
            </div>

        </form>

    </main>

</body>
</html>
