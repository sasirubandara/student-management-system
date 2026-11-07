<nav class="app-navigation" aria-label="Main navigation">

    <a href="{{ route('students.index') }}"
       class="app-navigation__brand">
        Student Management System
    </a>

    <div class="app-navigation__links">

        <a href="{{ route('students.index') }}">
            Students
        </a>

        @can('viewAny', \App\Models\User::class)
            <a href="{{ route('users.index') }}">
                Users
            </a>
        @endcan

        @can('viewArchived', \App\Models\Student::class)
            <a href="{{ route('students.archived') }}">
                Archived Students
            </a>
        @endcan

    </div>

    <div class="app-navigation__account">

        <span class="app-navigation__username">
            Welcome,
            <strong>{{ auth()->user()->name }}</strong>

            <span class="app-navigation__role">
                ({{ ucfirst(auth()->user()->role) }})
            </span>
        </span>

        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <button type="submit" class="app-navigation__logout">
                Logout
            </button>
        </form>

    </div>

</nav>