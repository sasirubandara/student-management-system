@php
    $academicYears = $academicYears ?? collect();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Application Settings | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="settings-page">

        <header class="settings-page__header">
            <div>
                <h1>Application Settings</h1>

                <p>
                    Manage institution information and application preferences.
                </p>
            </div>
        </header>

        @if (session('success'))
            <div
                class="settings-page__success"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="settings-page__validation"
                role="alert"
            >
                <strong>Please review your settings.</strong>

                <p>
                    Some fields contain invalid or missing information.
                </p>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('settings.update') }}"
            method="POST"
            class="settings-form"
        >
            @csrf
            @method('PATCH')

            {{-- Institution Information --}}

            <section
                class="settings-section"
                aria-labelledby="institution-settings-title"
            >
                <div class="settings-section__header">

                    <div
                        class="settings-section__icon"
                        aria-hidden="true"
                    >
                        🏫
                    </div>

                    <div>
                        <h2 id="institution-settings-title">
                            Institution Information
                        </h2>

                        <p>
                            Configure the institution's identity
                            and contact information.
                        </p>
                    </div>

                </div>

                <div class="settings-form__grid">

                    <div class="settings-form__field settings-form__field--full">

                        <label for="institution_name">
                            Institution Name
                            <span aria-hidden="true">*</span>
                        </label>

                        <input
                            type="text"
                            id="institution_name"
                            name="institution_name"
                            value="{{ old('institution_name', $settings['institution_name']) }}"
                            maxlength="255"
                            autocomplete="organization"
                            placeholder="Enter institution name"
                            required
                            @error('institution_name')
                                aria-invalid="true"
                                aria-describedby="institution-name-error"
                            @enderror
                        >

                        @error('institution_name')
                            <p
                                class="settings-form__error"
                                id="institution-name-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="settings-form__field">

                        <label for="institution_email">
                            Institution Email
                        </label>

                        <input
                            type="email"
                            id="institution_email"
                            name="institution_email"
                            value="{{ old('institution_email', $settings['institution_email']) }}"
                            maxlength="255"
                            autocomplete="email"
                            placeholder="office@example.org"
                            @error('institution_email')
                                aria-invalid="true"
                                aria-describedby="institution-email-error"
                            @enderror
                        >

                        @error('institution_email')
                            <p
                                class="settings-form__error"
                                id="institution-email-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="settings-form__field">

                        <label for="institution_phone">
                            Institution Phone
                        </label>

                        <input
                            type="tel"
                            id="institution_phone"
                            name="institution_phone"
                            value="{{ old('institution_phone', $settings['institution_phone']) }}"
                            maxlength="50"
                            autocomplete="tel"
                            placeholder="Enter phone number"
                            @error('institution_phone')
                                aria-invalid="true"
                                aria-describedby="institution-phone-error"
                            @enderror
                        >

                        @error('institution_phone')
                            <p
                                class="settings-form__error"
                                id="institution-phone-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="settings-form__field settings-form__field--full">

                        <label for="institution_address">
                            Institution Address
                        </label>

                        <textarea
                            id="institution_address"
                            name="institution_address"
                            maxlength="2000"
                            rows="4"
                            autocomplete="street-address"
                            placeholder="Enter institution address"
                            @error('institution_address')
                                aria-invalid="true"
                                aria-describedby="institution-address-error"
                            @enderror
                        >{{ old('institution_address', $settings['institution_address']) }}</textarea>

                        @error('institution_address')
                            <p
                                class="settings-form__error"
                                id="institution-address-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>

            {{-- Academic Preferences --}}

            <section
                class="settings-section"
                aria-labelledby="academic-settings-title"
            >
                <div class="settings-section__header">

                    <div
                        class="settings-section__icon"
                        aria-hidden="true"
                    >
                        🎓
                    </div>

                    <div>
                        <h2 id="academic-settings-title">
                            Academic Preferences
                        </h2>

                        <p>
                            Configure academic defaults for the institution.
                        </p>
                    </div>

                </div>

                <div class="settings-form__grid">

                    <div class="settings-form__field">

                        <label for="current_academic_year">
                            Current Academic Year
                            <span aria-hidden="true">*</span>
                        </label>

                        <input
                            type="number"
                            id="current_academic_year"
                            name="current_academic_year"
                            value="{{ old('current_academic_year', $settings['current_academic_year']) }}"
                            min="2000"
                            max="2100"
                            step="1"
                            list="registered-academic-years"
                            required
                            aria-describedby="academic-year-help{{ $errors->has('current_academic_year') ? ' academic-year-error' : '' }}"
                            @error('current_academic_year')
                                aria-invalid="true"
                            @enderror
                        >

                        <datalist id="registered-academic-years">
                            @foreach ($academicYears as $academicYear)
                                <option
                                    value="{{ $academicYear->year }}"
                                    label="{{ $academicYear->name }}"
                                ></option>
                            @endforeach
                        </datalist>

                        <p
                            class="settings-form__help"
                            id="academic-year-help"
                        >
                            Enter the academic year identifier.
                            Registered academic years may appear as suggestions.
                        </p>

                        @if ($academicYears->isNotEmpty())

                            <p class="settings-form__help">
                                Registered academic years:
                                @foreach ($academicYears as $academicYear)
                                    <a href="{{ route('academic-years.show', $academicYear) }}">
                                        {{ $academicYear->year }}
                                        ({{ $academicYear->name }})
                                    </a>{{ $loop->last ? '' : ', ' }}
                                @endforeach
                            </p>

                        @else

                            <p class="settings-form__help">
                                No academic calendar records have been registered yet.
                                You can continue using the numeric academic year setting.
                            </p>

                        @endif

                        @error('current_academic_year')
                            <p
                                class="settings-form__error"
                                id="academic-year-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>

            {{-- Localization Preferences --}}

            <section
                class="settings-section"
                aria-labelledby="localization-settings-title"
            >
                <div class="settings-section__header">

                    <div
                        class="settings-section__icon"
                        aria-hidden="true"
                    >
                        🌐
                    </div>

                    <div>
                        <h2 id="localization-settings-title">
                            Localization Preferences
                        </h2>

                        <p>
                            Configure preferred timezone and date formats.
                        </p>
                    </div>

                </div>

                <div class="settings-form__grid">

                    <div class="settings-form__field">

                        <label for="timezone">
                            Timezone
                            <span aria-hidden="true">*</span>
                        </label>

                        <select
                            id="timezone"
                            name="timezone"
                            required
                            aria-describedby="timezone-help{{ $errors->has('timezone') ? ' timezone-error' : '' }}"
                            @error('timezone')
                                aria-invalid="true"
                            @enderror
                        >
                            @foreach ($timezones as $timezone)
                                <option
                                    value="{{ $timezone }}"
                                    @selected(old('timezone', $settings['timezone']) === $timezone)
                                >
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>

                        <p
                            class="settings-form__help"
                            id="timezone-help"
                        >
                            Preferred timezone for displaying timestamps.
                        </p>

                        @error('timezone')
                            <p
                                class="settings-form__error"
                                id="timezone-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="settings-form__field">

                        <label for="date_format">
                            Date Format
                            <span aria-hidden="true">*</span>
                        </label>

                        <select
                            id="date_format"
                            name="date_format"
                            required
                            aria-describedby="date-format-help{{ $errors->has('date_format') ? ' date-format-error' : '' }}"
                            @error('date_format')
                                aria-invalid="true"
                            @enderror
                        >
                            @foreach ($dateFormats as $dateFormat)
                                <option
                                    value="{{ $dateFormat }}"
                                    @selected(old('date_format', $settings['date_format']) === $dateFormat)
                                >
                                    {{ now()->format($dateFormat) }}
                                    ({{ $dateFormat }})
                                </option>
                            @endforeach
                        </select>

                        <p
                            class="settings-form__help"
                            id="date-format-help"
                        >
                            Preferred format for displaying dates.
                        </p>

                        @error('date_format')
                            <p
                                class="settings-form__error"
                                id="date-format-error"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>

            <div class="settings-form__actions">

                <p>
                    Fields marked with * are required.
                </p>

                <div class="settings-form__buttons">

                    <a
                        href="{{ route('dashboard') }}"
                        class="settings-form__cancel"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="settings-form__submit"
                    >
                        Save Settings
                    </button>

                </div>

            </div>

        </form>

    </main>

</body>
</html>