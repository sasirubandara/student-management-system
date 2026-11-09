<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Teacher | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Edit Teacher Information</h1>
            <p>Update the teacher's personal and employment information.</p>
        </header>

        <a href="{{ route('teachers.show', $teacher) }}"
           class="students-page__button students-page__button--secondary">
            Back to Teacher Profile
        </a>

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

        <form action="{{ route('teachers.update', $teacher) }}"
              method="POST">

            @csrf
            @method('PUT')

            <div class="form-grid">

                {{-- Teacher Number --}}
                <div class="form-group">

                    <label for="teacher_number">Teacher Number *</label>

                    <input
                        type="text"
                        id="teacher_number"
                        name="teacher_number"
                        value="{{ old('teacher_number', $teacher->teacher_number) }}"
                        maxlength="20"
                        required
                    >

                    @error('teacher_number')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- First Name --}}
                <div class="form-group">

                    <label for="first_name">First Name *</label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name', $teacher->first_name) }}"
                        maxlength="100"
                        autocomplete="given-name"
                        required
                    >

                    @error('first_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Last Name --}}
                <div class="form-group">

                    <label for="last_name">Last Name *</label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="{{ old('last_name', $teacher->last_name) }}"
                        maxlength="100"
                        autocomplete="family-name"
                        required
                    >

                    @error('last_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Email --}}
                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $teacher->email) }}"
                        maxlength="255"
                        autocomplete="email"
                    >

                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Phone --}}
                <div class="form-group">

                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $teacher->phone) }}"
                        maxlength="30"
                        autocomplete="tel"
                    >

                    @error('phone')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Date of Birth --}}
                <div class="form-group">

                    <label for="date_of_birth">Date of Birth</label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="{{ old(
                            'date_of_birth',
                            $teacher->date_of_birth?->format('Y-m-d')
                        ) }}"
                    >

                    @error('date_of_birth')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Employment Date --}}
                <div class="form-group">

                    <label for="employment_date">Employment Date *</label>

                    <input
                        type="date"
                        id="employment_date"
                        name="employment_date"
                        value="{{ old(
                            'employment_date',
                            $teacher->employment_date->format('Y-m-d')
                        ) }}"
                        required
                    >

                    @error('employment_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Status --}}
                <div class="form-group">

                    <label for="status">Teacher Status *</label>

                    <select id="status" name="status" required>

                        <option value="active"
                            @selected(old('status', $teacher->status) === 'active')>
                            Active
                        </option>

                        <option value="inactive"
                            @selected(old('status', $teacher->status) === 'inactive')>
                            Inactive
                        </option>

                    </select>

                    @error('status')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

            <div class="form-actions">

                <button type="submit">
                    Update Teacher
                </button>

                <a href="{{ route('teachers.show', $teacher) }}"
                   class="students-page__button students-page__button--secondary">
                    Cancel
                </a>

            </div>

        </form>

    </main>

</body>
</html>