<nav class="app-navigation" aria-label="Main navigation">

    <!-- Application brand -->
    <a href="{{ route('students.index') }}"
       class="app-navigation__brand">
        Student Management System
    </a>

    <!-- Navigation links -->
    <div class="app-navigation__links">

        <a href="{{ route('students.index') }}">
            Students
        </a>

        <a href="{{ route('students.archived') }}">
            Archived Students
        </a>

    </div>

    <!-- Account information and logout -->
    <div class="app-navigation__account">

        <span class="app-navigation__username">
            Welcome,
            <strong>{{ auth()->user()->name }}</strong>
        </span>

        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <button type="submit" class="app-navigation__logout">
                Logout
            </button>
        </form>

    </div>

</nav>