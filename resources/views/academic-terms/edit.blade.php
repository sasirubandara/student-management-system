<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Academic Term | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">
        <header class="students-page__header"><div>
            <h1>Edit Academic Term</h1>
            <p>{{ $academicTerm->name }} — {{ $academicTerm->academicYear->name }}
            ({{ $academicTerm->academicYear->start_date->format('d M Y') }} – {{ $academicTerm->academicYear->end_date->format('d M Y') }})</p>
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
            <p class="students-search__description">
                All term dates must fall within the parent academic year and must not overlap existing terms.
            </p>
            <form action="{{ route('academic-terms.update', $academicTerm) }}" method="POST" class="student-edit-form">
                @csrf
                @method('PUT')
            <div class="student-edit-form__field">
                <label for="name">Term name</label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $academicTerm->name) }}" placeholder="e.g. First Term" maxlength="100" required
                       aria-invalid="@error('name') true @else false @enderror"
                       @error('name') aria-describedby="name-error" @enderror>
                @error('name')
                    <p id="name-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="term_number">Term sequence number</label>
                <input type="number" id="term_number" name="term_number"
                       value="{{ old('term_number', $academicTerm->term_number) }}" placeholder="e.g. 1" min="1" max="255" step="1" required
                       aria-invalid="@error('term_number') true @else false @enderror"
                       @error('term_number') aria-describedby="term_number-error" @enderror>
                @error('term_number')
                    <p id="term_number-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="start_date">Start date</label>
                <input type="date" id="start_date" name="start_date"
                       value="{{ old('start_date', $academicTerm->start_date->format('Y-m-d')) }}"  required
                       aria-invalid="@error('start_date') true @else false @enderror"
                       @error('start_date') aria-describedby="start_date-error" @enderror>
                @error('start_date')
                    <p id="start_date-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="student-edit-form__field">
                <label for="end_date">End date</label>
                <input type="date" id="end_date" name="end_date"
                       value="{{ old('end_date', $academicTerm->end_date->format('Y-m-d')) }}"  required
                       aria-invalid="@error('end_date') true @else false @enderror"
                       @error('end_date') aria-describedby="end_date-error" @enderror>
                @error('end_date')
                    <p id="end_date-error" class="student-edit-form__error">{{ $message }}</p>
                @enderror
            </div>

                <div class="student-edit-form__actions">
                    <button type="submit" class="students-page__button">Save Changes</button>
                    <a href="{{ route('academic-years.show', $academicTerm->academicYear) }}" class="students-search__clear">Cancel</a>
                </div>
            </form>
        </section>

    </main>
</body>
</html>
