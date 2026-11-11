<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display registered users with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    /**
     * Display the staff account creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create');
    }

    /**
     * Create a new staff account.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use (
            $validated,
            $auditLogger
        ): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]);

            $auditLogger->record(
                'user.created',
                $user,
                [
                    'role' => User::ROLE_STAFF,
                    'is_active' => true,
                ]
            );
        });

        return to_route('users.index')
            ->with('success', 'Staff account created successfully.');
    }

    /**
     * Update a user's role while preserving at least one active administrator.
     */
    public function updateRole(
        Request $request,
        User $user,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('updateRole', $user);

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in([
                    User::ROLE_ADMIN,
                    User::ROLE_STAFF,
                ]),
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $user,
            $validated,
            $auditLogger
        ): void {

            // Lock administrator records to serialize administrative changes.
            $administratorRecords = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'is_active']);

            // Confirm that the acting user is still an active administrator.
            $actingAdministrator = $administratorRecords->firstWhere(
                'id',
                $request->user()->getKey()
            );

            abort_unless(
                $actingAdministrator !== null && $actingAdministrator->is_active,
                403
            );

            $activeAdministratorCount = $administratorRecords
                ->where('is_active', true)
                ->count();

            // Lock the target account before changing its role.
            $targetUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $targetUser->isAdmin()
                && $targetUser->isActive()
                && $validated['role'] === User::ROLE_STAFF
                && $activeAdministratorCount <= 1
            ) {
                throw ValidationException::withMessages([
                    'role' => 'The last active administrator cannot be changed to staff. Assign administrator access to another active account first.',
                ]);
            }

            $previousRole = $targetUser->role;

            if ($previousRole !== $validated['role']) {
                $targetUser->update([
                    'role' => $validated['role'],
                ]);

                $auditLogger->record(
                    'user.role_updated',
                    $targetUser,
                    [
                        'changes' => [
                            'role' => [
                                'old' => $previousRole,
                                'new' => $validated['role'],
                            ],
                        ],
                    ]
                );
            }

        }, 3);

        $isSelfDemotion = $request->user()->getKey() === $user->getKey()
            && $validated['role'] === User::ROLE_STAFF;

        return to_route($isSelfDemotion ? 'students.index' : 'users.index')
            ->with('success', 'User role updated successfully.');
    }

    /**
     * Activate or deactivate a user account.
     */
    public function updateStatus(
        Request $request,
        User $user,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('updateStatus', $user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        DB::transaction(function () use (
            $request,
            $user,
            $isActive,
            $auditLogger
        ): void {

            // Lock administrator records to protect administrator access.
            $administratorRecords = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'is_active']);

            // Verify the acting user is still an active administrator.
            $actingAdministrator = $administratorRecords->firstWhere(
                'id',
                $request->user()->getKey()
            );

            abort_unless(
                $actingAdministrator !== null && $actingAdministrator->is_active,
                403
            );

            $activeAdministratorCount = $administratorRecords
                ->where('is_active', true)
                ->count();

            // Lock the target account before updating its status.
            $targetUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Administrators cannot deactivate their own accounts.
            if (
                $targetUser->getKey() === $request->user()->getKey()
                && ! $isActive
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot deactivate your own account.',
                ]);
            }

            // Preserve at least one active administrator.
            if (
                ! $isActive
                && $targetUser->isAdmin()
                && $targetUser->isActive()
                && $activeAdministratorCount <= 1
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'The last active administrator cannot be deactivated. Activate another administrator first.',
                ]);
            }

            $previousStatus = $targetUser->isActive();

            if ($previousStatus !== $isActive) {
                $targetUser->update([
                    'is_active' => $isActive,
                ]);

                $auditLogger->record(
                    'user.status_updated',
                    $targetUser,
                    [
                        'changes' => [
                            'is_active' => [
                                'old' => $previousStatus,
                                'new' => $isActive,
                            ],
                        ],
                    ]
                );
            }

        }, 3);

        return to_route('users.index')
            ->with(
                'success',
                $isActive
                    ? 'User account activated successfully.'
                    : 'User account deactivated successfully.'
            );
    }
}