<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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

        return view('academic-classes.create');
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', AcademicClass::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:academic_classes,code'],
            'grade_level' => ['required', 'integer', 'between:1,13'],
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    AcademicClass::STATUS_ACTIVE,
                    AcademicClass::STATUS_INACTIVE,
                ]),
            ],
        ]);

        AcademicClass::create($validated);

        return to_route('academic-classes.index')
            ->with('success', 'Academic class created successfully.');
    }

    /**
     * Display an individual class and its assigned subjects.
     */
    public function show(AcademicClass $academicClass): View
    {
        Gate::authorize('view', $academicClass);

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
            'availableSubjects'
        ));
    }

    /**
     * Display the class editing form.
     */
    public function edit(AcademicClass $academicClass): View
    {
        Gate::authorize('update', $academicClass);

        return view('academic-classes.edit', compact('academicClass'));
    }

    /**
     * Update an existing class.
     */
    public function update(
        Request $request,
        AcademicClass $academicClass
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
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    AcademicClass::STATUS_ACTIVE,
                    AcademicClass::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $academicClass->update($validated);

        return to_route('academic-classes.show', $academicClass)
            ->with('success', 'Academic class updated successfully.');
    }

    /**
     * Soft-delete an academic class.
     */
    public function destroy(AcademicClass $academicClass): RedirectResponse
    {
        Gate::authorize('delete', $academicClass);

        $academicClass->delete();

        return to_route('academic-classes.index')
            ->with('success', 'Academic class deleted successfully.');
    }
}