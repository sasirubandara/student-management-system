<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AcademicCalendar\AcademicYearService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    /**
     * Display a searchable, paginated academic-year listing.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AcademicYear::class);

        $search = trim((string) $request->query('search', ''));

        $academicYears = AcademicYear::query()
            ->withCount('terms')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $query->orWhere('year', (int) $search);
                    }
                });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('academic-years.index', compact(
            'academicYears',
            'search'
        ));
    }

    /**
     * Display the academic-year creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', AcademicYear::class);

        return view('academic-years.create');
    }

    /**
     * Store an academic year using the validated service.
     */
    public function store(
        StoreAcademicYearRequest $request,
        AcademicYearService $service,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $academicYear = $service->create(
            $request->validated(),
            function (
                AcademicYear $year,
                array $changedFields
            ) use ($auditLogger): void {
                $auditLogger->record(
                    'academic_year.created',
                    $year,
                    [
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        );

        return to_route('academic-years.show', $academicYear)
            ->with('success', 'Academic year created successfully.');
    }

    /**
     * Display an academic year and its terms.
     */
    public function show(AcademicYear $academicYear): View
    {
        Gate::authorize('view', $academicYear);

        $academicYear->load([
            'terms' => fn ($query) => $query
                ->orderBy('term_number')
                ->orderBy('start_date'),
        ]);

        return view('academic-years.show', compact('academicYear'));
    }

    /**
     * Display the editing form.
     */
    public function edit(AcademicYear $academicYear): View
    {
        Gate::authorize('update', $academicYear);

        return view('academic-years.edit', compact('academicYear'));
    }

    /**
     * Update an academic year using the validated service.
     */
    public function update(
        UpdateAcademicYearRequest $request,
        AcademicYear $academicYear,
        AcademicYearService $service,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $academicYear = $service->update(
            $academicYear,
            $request->validated(),
            function (
                AcademicYear $year,
                array $changedFields
            ) use ($auditLogger): void {
                $auditLogger->record(
                    'academic_year.updated',
                    $year,
                    [
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        );

        return to_route('academic-years.show', $academicYear)
            ->with('success', 'Academic year updated successfully.');
    }
}
