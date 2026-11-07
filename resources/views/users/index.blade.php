<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>User Management</h1>
                <p>View and manage registered system users.</p>
            </div>

            <div>
                <a href="{{ route('users.create') }}"
                   class="students-page__button">
                    Create Staff Account
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="student-edit-form__success">
                {{ session('success') }}
            </div>
        @endif

        @error('role')
            <div class="student-edit-form__error" role="alert">
                {{ $message }}
            </div>
        @enderror

        @error('is_active')
            <div class="student-edit-form__error" role="alert">
                {{ $message }}
            </div>
        @enderror

        <!-- Search users -->
        <section class="students-search-section">

            <form action="{{ route('users.index') }}"
                  method="GET"
                  class="students-search">

                <div class="students-search__input-wrapper">

                    <label for="user-search" class="sr-only">
                        Search users
                    </label>

                    <input
                        type="search"
                        id="user-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by name or email..."
                        maxlength="255"
                    >

                </div>

                <button type="submit" class="students-page__button">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ route('users.index') }}"
                       class="students-search__clear">
                        Clear Search
                    </a>
                @endif

            </form>

        </section>

        <!-- User count -->
        <section class="students-page__summary">

            <p>
                {{ $search !== '' ? 'Matching users:' : 'Total registered users:' }}
                <strong>{{ $users->total() }}</strong>
            </p>

            @if ($search !== '')
                <p class="students-search__description">
                    Search results for:
                    <strong>{{ $search }}</strong>
                </p>
            @endif

        </section>

        <!-- User listing -->
        @if ($users->isEmpty())

            <section class="students-page__empty">

                @if ($search !== '')
                    <h2>No Matching Users</h2>

                    <p>
                        No users matched your search.
                        Try another name or email address.
                    </p>

                    <a href="{{ route('users.index') }}"
                       class="students-page__button">
                        View All Users
                    </a>
                @else
                    <h2>No Users Found</h2>

                    <p>
                        There are currently no registered users.
                    </p>
                @endif

            </section>

        @else

            <div class="students-page__table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Registration Date</th>
                            <th>Manage Role</th>
                            <th>Manage Account</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($users as $user)

                            <tr>

                                <td>
                                    <strong>{{ $user->name }}</strong>
                                </td>

                                <td>
                                    {{ $user->email }}
                                </td>

                                <td>
                                    {{ ucfirst($user->role) }}
                                </td>

                                <td>
                                    @if ($user->is_active)
                                        Active
                                    @else
                                        Inactive
                                    @endif
                                </td>

                                <td>
                                    {{ $user->created_at?->format('d M Y') ?? 'Not available' }}
                                </td>

                                <td>

                                    <form
                                        action="{{ route('users.role.update', $user) }}"
                                        method="POST"
                                    >

                                        @csrf
                                        @method('PATCH')

                                        <label
                                            for="role-{{ $user->id }}"
                                            class="sr-only"
                                        >
                                            Role for {{ $user->name }}
                                        </label>

                                        <select
                                            id="role-{{ $user->id }}"
                                            name="role"
                                        >
                                            <option
                                                value="{{ \App\Models\User::ROLE_STAFF }}"
                                                @selected($user->role === \App\Models\User::ROLE_STAFF)
                                            >
                                                Staff
                                            </option>

                                            <option
                                                value="{{ \App\Models\User::ROLE_ADMIN }}"
                                                @selected($user->role === \App\Models\User::ROLE_ADMIN)
                                            >
                                                Admin
                                            </option>
                                        </select>

                                        <button
                                            type="submit"
                                            class="students-page__button"
                                        >
                                            Update Role
                                        </button>

                                    </form>

                                </td>

                                <td>

                                    @if ((int) auth()->id() !== (int) $user->getKey())

                                        <form
                                            action="{{ route('users.status.update', $user) }}"
                                            method="POST"
                                        >

                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value="{{ $user->is_active ? '0' : '1' }}"
                                            >

                                            <button
                                                type="submit"
                                                class="students-page__button"
                                            >
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>

                                        </form>

                                    @else

                                        <span>Current account</span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <div class="students-page__pagination">
                {{ $users->links() }}
            </div>

        @endif

    </main>

</body>
</html>