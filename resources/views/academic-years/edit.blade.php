<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit {{ $academicYear->name }} | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">
        <header class="students-page__header"><div>
            <h1>Edit Academic Year</h1>
            <p>Update {{ $academicYear->name }} without excluding existing academic terms.</p>
        </div></header>
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
        <section class="students-page__form-wrapper">
            <form action="{{ route('academic-years.update', $academicYear) }}" method="POST" class="student-edit-form">
                @csrf
                @method('PUT')
            <div class="student-edit-form__field">
                <label for="year">Year identifier</label>
                <input type="number" id="year" name="year"
                       value="{{ old('year', $academicYear->year) }}" placeholder="e.g. 2027" min="2000" max="2100" step="1" required
                       aria-invalid="@error('year') true @else false @enderror"
                       @error('year') aria-describedby="year-error" @enderror>
                @error('year')
                    <p id="year-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="name">Academic year name</label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $academicYear->name) }}" placeholder="e.g. Academic Year 2027/2028" maxlength="100" required
                       aria-invalid="@error('name') true @else false @enderror"
                       @error('name') aria-describedby="name-error" @enderror>
                @error('name')
                    <p id="name-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="start_date">Start date</label>
                <input type="date" id="start_date" name="start_date"
                       value="{{ old('start_date', $academicYear->start_date->format('Y-m-d')) }}"  required
                       aria-invalid="@error('start_date') true @else false @enderror"
                       @error('start_date') aria-describedby="start_date-error" @enderror>
                @error('start_date')
                    <p id="start_date-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="end_date">End date</label>
                <input type="date" id="end_date" name="end_date"
                       value="{{ old('end_date', $academicYear->end_date->format('Y-m-d')) }}"  required
                       aria-invalid="@error('end_date') true @else false @enderror"
                       @error('end_date') aria-describedby="end_date-error" @enderror>
                @error('end_date')
                    <p id="end_date-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>

                <div class="student-edit-form__actions">
                    <button type="submit" class="students-page__button">Save Changes</button>
                    <a href="{{ route('academic-years.show', $academicYear) }}" class="students-search__clear">Cancel</a>
                </div>
            </form>
        </section>

    </main>
</body>
</html>
