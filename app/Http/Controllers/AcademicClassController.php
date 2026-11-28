<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Services\AcademicCalendar\AcademicCalendarTransaction;
use App\Services\AuditLogger;
use App\Services\Timetables\TimetableTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicClassController extends Controller
{
    /**
     * Display academic classes with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AcademicClass::class);

        $search = trim((string) $request->query('search', ''));

        $academicClasses = AcademicClass::query()
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

        return view('academic-classes.index', compact(
            'academicClasses',
            'search'
        ));
    }

    /**
     * Display the class creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', AcademicClass::class);

        $academicYears = AcademicYear::query()
            ->orderByDesc('year')
            ->get();

        return view('academic-classes.create', compact('academicYears'));
    }

    /**
     * Store a newly created class.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        AcademicCalendarTransaction $calendarTransaction
    ): RedirectResponse {
        Gate::authorize('create', AcademicClass::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:academic_classes,code'],
            'grade_level' => ['required', 'integer', 'between:1,13'],
            'academic_year' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::exists('academic_years', 'year'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    AcademicClass::STATUS_ACTIVE,
                    AcademicClass::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $academicClass = $calendarTransaction->run(function () use (
            $validated,
            $auditLogger
        ): AcademicClass {
            // Recheck after acquiring the calendar lock.
            // This prevents a concurrent academic-year renumbering
            // from invalidating the earlier request validation.
            $yearExists = AcademicYear::query()
                ->where('year', $validated['academic_year'])
                ->exists();

            if (! $yearExists) {
                throw ValidationException::withMessages([
                    'academic_year' => 'The selected academic year is no longer registered.',
                ]);
            }

            $academicClass = AcademicClass::query()->create($validated);

            $auditLogger->record(
                'academic_class.created',
                $academicClass,
                [
                    'changed_fields' => array_keys($validated),
                ]
            );

            return $academicClass;
        });

        return to_route('academic-classes.index')
            ->with('success', 'Academic class created successfully.');
    }

    /**
     * Display an individual class and its assigned subjects.
     */
    public function show(AcademicClass $academicClass): View
    {
        Gate::authorize('view', $academicClass);

        $registeredAcademicYear = AcademicYear::query()
            ->where('year', $academicClass->academic_year)
            ->first();

        // Load the subjects already assigned to this class.
        $academicClass->load('subjects');

        // Find active subjects that haven't been assigned yet.
        $availableSubjects = Subject::query()
            ->where('status', Subject::STATUS_ACTIVE)
            ->whereNotIn(
                'id',
                $academicClass->subjects->modelKeys()
            )
            ->orderBy('name')
            ->get();

        return view('academic-classes.show', compact(
            'academicClass',
            'availableSubjects',
            'registeredAcademicYear'
        ));
    }

    /**
     * Display the class editing form.
     */
    public function edit(AcademicClass $academicClass): View
    {
        Gate::authorize('update', $academicClass);

        $academicYears = AcademicYear::query()
            ->orderByDesc('year')
            ->get();

        return view('academic-classes.edit', compact(
            'academicClass',
            'academicYears'
        ));
    }

    /**
     * Update an existing class.
     */
    public function update(
        Request $request,
        AcademicClass $academicClass,
        AuditLogger $auditLogger,
        TimetableTransaction $timetableTransaction,
        AcademicCalendarTransaction $calendarTransaction
    ): RedirectResponse {
        Gate::authorize('update', $academicClass);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],

            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('academic_classes', 'code')
                    ->ignore($academicClass->id),
            ],

            'grade_level' => ['required', 'integer', 'between:1,13'],
            'academic_year' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::when(
                    (string) $request->input('academic_year') !==
                        (string) $academicClass->academic_year,
                    [Rule::exists('academic_years', 'year')]
                ),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    AcademicClass::STATUS_ACTIVE,
                    AcademicClass::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $calendarTransaction->run(function () use (
            $academicClass,
            $validated,
            $auditLogger,
            $timetableTransaction
        ): void {
            $timetableTransaction->run(function () use (
                $academicClass,
                $validated,
                $auditLogger
            ): void {
                $lockedClass = AcademicClass::query()
                    ->whereKey($academicClass->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedClass->fill($validated);

                $changedFields = array_keys($lockedClass->getDirty());

                if ($changedFields === []) {
                    return;
                }

                // Recheck the academic year inside the locks.
                // Historical classes may retain an unregistered
                // year if that value has not changed.
                if (in_array('academic_year', $changedFields, true)) {
                    $yearExists = AcademicYear::query()
                        ->where('year', $lockedClass->academic_year)
                        ->exists();

                    if (! $yearExists) {
                        throw ValidationException::withMessages([
                            'academic_year' => 'The selected academic year is no longer registered.',
                        ]);
                    }
                }

                $protectedFields = [
                    'academic_year',
                    'status',
                ];

                $hasProtectedChange = array_intersect(
                    $changedFields,
                    $protectedFields
                ) !== [];

                if ($hasProtectedChange) {
                    $hasScheduledLessons = TimetableEntry::query()
                        ->whereHas(
                            'teachingAssignment',
                            function (Builder $query) use ($lockedClass): void {
                                $query->where(
                                    'academic_class_id',
                                    $lockedClass->id
                                );
                            }
                        )
                        ->exists();

                    if ($hasScheduledLessons) {
                        throw ValidationException::withMessages([
                            'academic_year' => 'This academic class has scheduled lessons. '
                                .'Remove its timetable entries before changing '
                                .'its academic year or status.',
                        ]);
                    }
                }

                $lockedClass->save();

                $auditLogger->record(
                    'academic_class.updated',
                    $lockedClass,
                    [
                        'changed_fields' => $changedFields,
                    ]
                );
            });
        });

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Academic class updated successfully.');
    }

    /**
     * Soft-delete an academic class.
     */
    public function destroy(
        AcademicClass $academicClass,
        AuditLogger $auditLogger,
        TimetableTransaction $timetableTransaction
    ): RedirectResponse {
        Gate::authorize('delete', $academicClass);

        $timetableTransaction->run(function () use (
            $academicClass,
            $auditLogger
        ): void {
            $lockedClass = AcademicClass::query()
                ->whereKey($academicClass->id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasScheduledLessons = TimetableEntry::query()
                ->whereHas(
                    'teachingAssignment',
                    function (Builder $query) use ($lockedClass): void {
                        $query->where(
                            'academic_class_id',
                            $lockedClass->id
                        );
                    }
                )
                ->exists();

            if ($hasScheduledLessons) {
                throw ValidationException::withMessages([
                    'academic_class_id' => 'This academic class has scheduled lessons. '
                        .'Remove its timetable entries before archiving it.',
                ]);
            }

            $auditProperties = [
                'record' => [
                    'name' => $lockedClass->name,
                    'code' => $lockedClass->code,
                    'academic_year' => $lockedClass->academic_year,
                ],
                'changed_fields' => ['deleted_at'],
            ];

            $lockedClass->delete();

            $auditLogger->record(
                'academic_class.archived',
                $lockedClass,
                $auditProperties
            );
        });

        return to_route('academic-classes.index')
            ->with('success', 'Academic class deleted successfully.');
    }
}
