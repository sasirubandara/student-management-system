<section
    class="profile-section"
    aria-labelledby="password-security-title"
>

    <h2 id="password-security-title">
        Password and Security
    </h2>

    <p class="profile-section__description">
        Change your password to keep your account secure.
    </p>

    @if (session('password_success'))
        <div
            class="profile-page__success profile-section__message"
            role="status"
        >
            {{ session('password_success') }}
        </div>
    @endif

    @if ($errors->updatePassword->any())
        <div
            class="profile-page__validation profile-section__message"
            role="alert"
        >
            <strong>Please review your password information.</strong>

            <ul>
                @foreach ($errors->updatePassword->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('profile.password.update') }}"
        method="POST"
        class="profile-form"
    >
        @csrf
        @method('PUT')

        <div class="profile-form__field">

            <label for="security-current-password">
                Current Password
            </label>

            <input
                type="password"
                id="security-current-password"
                name="current_password"
                autocomplete="current-password"
                required
                @error('current_password', 'updatePassword')
                    aria-invalid="true"
                    aria-describedby="security-current-password-error"
                @enderror
            >

            @error('current_password', 'updatePassword')
                <p
                    class="profile-form__error"
                    id="security-current-password-error"
                >
                    {{ $message }}
                </p>
            @enderror

        </div>

        <div class="profile-form__field">

            <label for="new-password">
                New Password
            </label>

            <input
                type="password"
                id="new-password"
                name="password"
                autocomplete="new-password"
                minlength="12"
                required
                aria-describedby="new-password-help{{ $errors->updatePassword->has('password') ? ' new-password-error' : '' }}"
                @error('password', 'updatePassword')
                    aria-invalid="true"
                @enderror
            >

            <p
                class="profile-form__help"
                id="new-password-help"
            >
                Use at least 12 characters and choose a password
                that is different from your current password.
            </p>

            @error('password', 'updatePassword')
                <p
                    class="profile-form__error"
                    id="new-password-error"
                >
                    {{ $message }}
                </p>
            @enderror

        </div>

        <div class="profile-form__field">

            <label for="password-confirmation">
                Confirm New Password
            </label>

            <input
                type="password"
                id="password-confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                minlength="12"
                required
            >

        </div>

        <div class="profile-form__actions">

            <button
                type="submit"
                class="profile-form__submit"
            >
                Update Password
            </button>

        </div>

        <p class="profile-form__help">
            Changing your password invalidates previously
            remembered sign-ins. Other authenticated sessions
            may require you to sign in again.
        </p>

    </form>

</section>