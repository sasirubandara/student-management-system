<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <main class="auth-page">

        <section class="auth-card">

            <div class="auth-card__header">
                <h1>Welcome Back</h1>
                <p>Sign in to your Student Management System account.</p>
            </div>

            <!-- Logout or other status messages -->
            @if (session('status'))
                <div class="auth-message auth-message--success">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login form -->
            <form action="{{ route('login.store') }}" method="POST">

                @csrf

                <!-- Email -->
                <div class="auth-form__field">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email address"
                        autocomplete="username"
                        maxlength="255"
                        required
                        autofocus
                    >

                    @error('email')
                        <span class="auth-form__error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

                <!-- Password -->
                <div class="auth-form__field">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    @error('password')
                        <span class="auth-form__error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

                <!-- Remember me -->
                <div class="auth-form__remember">

                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                    >

                    <label for="remember">Remember me</label>

                </div>

                <!-- Login button -->
                <button type="submit" class="auth-form__button">
                    Sign In
                </button>

            </form>

            <footer class="auth-card__footer">
                Student Management System
            </footer>

        </section>

    </main>

</body>
</html>