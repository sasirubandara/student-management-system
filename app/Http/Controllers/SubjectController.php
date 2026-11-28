<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Services\AuditLogger;
use App\Services\Timetables\TimetableTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        AuditLogger $auditLogger,
        TimetableTransaction $timetableTransaction
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

        $timetableTransaction->run(function () use (
            $subject,
            $validated,
            $auditLogger
        ): void {
            $lockedSubject = Subject::query()
                ->whereKey($subject->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSubject->fill($validated);

            $changedFields = array_keys($lockedSubject->getDirty());

            if ($changedFields === []) {
                return;
            }

            if (
                in_array('status', $changedFields, true)
                && $lockedSubject->status !== Subject::STATUS_ACTIVE
            ) {
                $hasScheduledLessons = TimetableEntry::query()
                    ->whereHas(
                        'teachingAssignment',
                        function (Builder $query) use ($lockedSubject): void {
                            $query->where(
                                'subject_id',
                                $lockedSubject->id
                            );
                        }
                    )
                    ->exists();

                if ($hasScheduledLessons) {
                    throw ValidationException::withMessages([
                        'status' => 'This subject has scheduled lessons. '
                            .'Remove the timetable entries before '
                            .'deactivating the subject.',
                    ]);
                }
            }

            $lockedSubject->save();

            $auditLogger->record(
                'subject.updated',
                $lockedSubject,
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
        AuditLogger $auditLogger,
        TimetableTransaction $timetableTransaction
    ): RedirectResponse {
        Gate::authorize('delete', $subject);

        $timetableTransaction->run(function () use (
            $subject,
            $auditLogger
        ): void {
            $lockedSubject = Subject::query()
                ->whereKey($subject->id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasScheduledLessons = TimetableEntry::query()
                ->whereHas(
                    'teachingAssignment',
                    function (Builder $query) use ($lockedSubject): void {
                        $query->where(
                            'subject_id',
                            $lockedSubject->id
                        );
                    }
                )
                ->exists();

            if ($hasScheduledLessons) {
                throw ValidationException::withMessages([
                    'subject_id' => 'This subject has scheduled lessons. '
                        .'Remove the timetable entries before archiving it.',
                ]);
            }

            $auditProperties = [
                'record' => [
                    'name' => $lockedSubject->name,
                    'code' => $lockedSubject->code,
                ],
                'changed_fields' => ['deleted_at'],
            ];

            $lockedSubject->delete();

            $auditLogger->record(
                'subject.archived',
                $lockedSubject,
                $auditProperties
            );
        });

        return to_route('subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}
