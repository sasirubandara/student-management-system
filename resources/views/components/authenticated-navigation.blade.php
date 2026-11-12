@php
    $notificationUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<nav class="app-navigation" aria-label="Main navigation">

    <a href="{{ route('dashboard') }}"
       class="app-navigation__brand">
        Student Management System
    </a>

    <div class="app-navigation__links">

        <a href="{{ route('dashboard') }}">
            Dashboard
        </a>

        <a href="{{ route('students.index') }}">
            Students
        </a>

        @can('viewAny', \App\Models\Teacher::class)
            <a href="{{ route('teachers.index') }}">
                Teachers
            </a>
        @endcan

        @can('viewAny', \App\Models\StudentEnrollment::class)
            <a href="{{ route('student-enrollments.index') }}">
                Enrollments
            </a>
        @endcan

        @can('viewAny', \App\Models\AttendanceSession::class)
            <a href="{{ route('attendance-sessions.index') }}">
                Attendance
            </a>
        @endcan

        @can('viewAny', \App\Models\Examination::class)
            <a href="{{ route('examinations.index') }}">
                Examinations
            </a>
        @endcan

        @can('viewAny', \App\Models\Student::class)
            <a href="{{ route('reports.index') }}">
                Reports
            </a>
        @endcan

        @can('viewAny', \App\Models\User::class)
            <a href="{{ route('users.index') }}">
                Users
            </a>
        @endcan

        @can('viewAny', \App\Models\AuditLog::class)
            <a href="{{ route('audit-logs.index') }}">
                Audit Logs
            </a>
        @endcan

        @can('viewArchived', \App\Models\Student::class)
            <a href="{{ route('students.archived') }}">
                Archived Students
            </a>
        @endcan

        @can('viewAny', \App\Models\AcademicClass::class)
            <a href="{{ route('academic-classes.index') }}">
                Classes
            </a>
        @endcan

        @can('viewAny', \App\Models\Subject::class)
            <a href="{{ route('subjects.index') }}">
                Subjects
            </a>
        @endcan

        <a href="{{ route('notifications.index') }}"
           aria-label="Notifications">

            <span aria-hidden="true">🔔</span>
            Notifications

            @if ($notificationUnreadCount > 0)
                <span class="app-navigation__notification-badge">
                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                </span>
            @endif

        </a>

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