@inject('dateFormatter', 'App\Services\DateFormattingService')

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page profile-page">

        <header class="page-header">
            <div>
                <h1>My Profile</h1>

                <p>
                    Manage your personal information and view
                    your account details.
                </p>
            </div>
        </header>

        @if (session('success'))
            <div class="profile-page__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="profile-page__validation" role="alert">

                <strong>Please review your profile information.</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        <section
            class="profile-section"
            aria-labelledby="personal-information-title"
        >

            <h2 id="personal-information-title">
                Personal Information
            </h2>

            <p class="profile-section__description">
                Update your name and email address.
            </p>

            <form
                action="{{ route('profile.update') }}"
                method="POST"
                class="profile-form"
            >
                @csrf
                @method('PATCH')

                <div class="profile-form__field">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        maxlength="255"
                        autocomplete="name"
                        required
                        @error('name')
                            aria-invalid="true"
                            aria-describedby="profile-name-error"
                        @enderror
                    >

                    @error('name')
                        <p
                            class="profile-form__error"
                            id="profile-name-error"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="profile-form__field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        maxlength="255"
                        autocomplete="email"
                        required
                        aria-describedby="profile-email-help{{ $errors->has('email') ? ' profile-email-error' : '' }}"
                        @error('email')
                            aria-invalid="true"
                        @enderror
                    >

                    <p
                        class="profile-form__help"
                        id="profile-email-help"
                    >
                        Your email address is used to sign in
                        to your account.
                    </p>

                    @error('email')
                        <p
                            class="profile-form__error"
                            id="profile-email-error"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="profile-form__field">

                    <label for="profile-current-password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="profile-current-password"
                        name="current_password"
                        autocomplete="current-password"
                        aria-describedby="profile-password-help{{ $errors->has('current_password') ? ' profile-password-error' : '' }}"
                        @error('current_password')
                            aria-invalid="true"
                        @enderror
                    >

                    <p
                        class="profile-form__help"
                        id="profile-password-help"
                    >
                        Required only when changing your email address.
                    </p>

                    @error('current_password')
                        <p
                            class="profile-form__error"
                            id="profile-password-error"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="profile-form__actions">

                    <button
                        type="submit"
                        class="profile-form__submit"
                    >
                        Save Profile
                    </button>

                </div>

            </form>

        </section>

        @include('profile.partials.update-password-form')

        <section
            class="profile-section"
            aria-labelledby="account-information-title"
        >

            <h2 id="account-information-title">
                Account Information
            </h2>

            <p class="profile-section__description">
                Your account role and activation status are
                managed by the application's administrators.
            </p>

            <dl class="profile-account-details">

                <div>
                    <dt>Account Role</dt>

                    <dd>
                        {{ $user->isAdmin() ? 'Administrator' : 'Staff' }}
                    </dd>
                </div>

                <div>
                    <dt>Account Status</dt>

                    <dd>
                        {{ $user->isActive() ? 'Active' : 'Inactive' }}
                    </dd>
                </div>

                <div>
                    <dt>Member Since</dt>

                    <dd>
                        {{ $dateFormatter->date($user->created_at) }}
                    </dd>
                </div>

            </dl>

        </section>

        <a href="{{ route('dashboard') }}">
            Back to Dashboard
        </a>

    </main>

</body>
</html>