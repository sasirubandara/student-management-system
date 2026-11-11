<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const MODULES = [
        'auth' => 'Authentication',
        'user' => 'User Management',
        'student' => 'Students and Documents',
        'teacher' => 'Teachers',
        'enrollment' => 'Student Enrollments',
        'attendance' => 'Attendance',
        'examination' => 'Examinations and Results',
        'academic_class' => 'Academic Classes and Assignments',
        'subject' => 'Subjects',
        'teaching_assignment' => 'Teaching Assignments',
    ];

    /**
     * Display audit logs with search and filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'event' => [
                'nullable',
                'string',
                'max:100',
            ],

            'module' => [
                'nullable',
                Rule::in(array_keys(self::MODULES)),
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ]);

        $search = trim($validated['search'] ?? '');

        $auditQuery = AuditLog::query()
            ->with('user')

            ->when($search !== '', function (
                Builder $query
            ) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use (
                    $term
                ): void {
                    $query->where('event', 'like', $term)
                        ->orWhere('auditable_type', 'like', $term)
                        ->orWhere('auditable_id', 'like', $term)
                        ->orWhereHas('user', function (
                            Builder $userQuery
                        ) use ($term): void {
                            $userQuery->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })

            ->when(
                ! empty($validated['event']),
                function (Builder $query) use ($validated): void {
                    $query->where('event', $validated['event']);
                }
            )

            ->when(
                ! empty($validated['module']),
                function (Builder $query) use ($validated): void {
                    $query->where(
                        'event',
                        'like',
                        $validated['module'] . '.%'
                    );
                }
            )

            ->when(
                ! empty($validated['user_id']),
                function (Builder $query) use ($validated): void {
                    $query->where('user_id', $validated['user_id']);
                }
            )

            ->when(
                ! empty($validated['date_from']),
                function (Builder $query) use ($validated): void {
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $validated['date_from']
                    );
                }
            )

            ->when(
                ! empty($validated['date_to']),
                function (Builder $query) use ($validated): void {
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $validated['date_to']
                    );
                }
            );

        $auditLogs = $auditQuery
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Get available event names for the filter.
        $events = AuditLog::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        // Display only users who have generated audit records.
        $users = User::query()
            ->whereIn(
                'id',
                AuditLog::query()
                    ->select('user_id')
                    ->whereNotNull('user_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $modules = self::MODULES;

        return view('audit-logs.index', compact(
            'auditLogs',
            'events',
            'users',
            'modules',
            'validated'
        ));
    }

    /**
     * Display an individual audit record.
     */
    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load('user');

        return view('audit-logs.show', compact('auditLog'));
    }
}