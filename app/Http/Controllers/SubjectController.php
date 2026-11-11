<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    /**
     * Display subjects with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Subject::class);

        $search = trim((string) $request->query('search', ''));

        $subjects = Subject::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('subjects.index', compact('subjects', 'search'));
    }

    /**
     * Display the subject creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', Subject::class);

        return view('subjects.create');
    }

    /**
     * Store a newly created subject.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('create', Subject::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    Subject::STATUS_ACTIVE,
                    Subject::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $subject = DB::transaction(function () use (
            $validated,
            $auditLogger
        ): Subject {
            $subject = Subject::create($validated);

            $auditLogger->record(
                'subject.created',
                $subject,
                [
                    'changed_fields' => array_keys($validated),
                ]
            );

            return $subject;
        });

        return to_route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Display an individual subject.
     */
    public function show(Subject $subject): View
    {
        Gate::authorize('view', $subject);

        return view('subjects.show', compact('subject'));
    }

    /**
     * Display the subject editing form.
     */
    public function edit(Subject $subject): View
    {
        Gate::authorize('update', $subject);

        return view('subjects.edit', compact('subject'));
    }

    /**
     * Update an existing subject.
     */
    public function update(
        Request $request,
        Subject $subject,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $subject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],

            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects', 'code')->ignore($subject->id),
            ],

            'description' => ['nullable', 'string', 'max:5000'],

            'status' => [
                'required',
                Rule::in([
                    Subject::STATUS_ACTIVE,
                    Subject::STATUS_INACTIVE,
                ]),
            ],
        ]);

        DB::transaction(function () use (
            $subject,
            $validated,
            $auditLogger
        ): void {
            $subject->fill($validated);

            $changedFields = array_keys($subject->getDirty());

            if ($changedFields === []) {
                return;
            }

            $subject->save();

            $auditLogger->record(
                'subject.updated',
                $subject,
                [
                    'changed_fields' => $changedFields,
                ]
            );
        });

        return to_route('subjects.show', $subject)
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Soft-delete a subject.
     */
    public function destroy(
        Subject $subject,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $subject);

        DB::transaction(function () use (
            $subject,
            $auditLogger
        ): void {
            $auditProperties = [
                'record' => [
                    'name' => $subject->name,
                    'code' => $subject->code,
                ],
                'changed_fields' => ['deleted_at'],
            ];

            $subject->delete();

            $auditLogger->record(
                'subject.archived',
                $subject,
                $auditProperties
            );
        });

        return to_route('subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}