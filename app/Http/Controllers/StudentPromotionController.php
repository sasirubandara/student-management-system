<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentPromotionRequest;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Services\StudentPromotions\StudentPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentPromotionController extends Controller
{
    /**
     * Display promotion history.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', StudentPromotion::class);

        $promotions = StudentPromotion::query()
            ->with([
                'student',
                'sourceEnrollment.academicClass',
                'destinationEnrollment.academicClass',
                'promotedBy',
            ])
            ->latest('id')
            ->paginate(10);

        return view('student-promotions.index', compact(
            'promotions'
        ));
    }

    /**
     * Display the individual promotion form.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', StudentPromotion::class);

        $sourceEnrollments = StudentEnrollment::query()
            ->with(['student', 'academicClass'])
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', function ($query): void {
                $query->where('status', 'active');
            })
            ->whereHas('academicClass', function ($query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE);
            })
            ->orderBy('id')
            ->get();

        $academicClasses = AcademicClass::query()
            ->where('status', AcademicClass::STATUS_ACTIVE)
            ->whereIn(
                'academic_year',
                AcademicYear::query()->select('year')
            )
            ->orderBy('academic_year')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $selectedSourceId = $request->query(
            'source_enrollment_id'
        );

        return view('student-promotions.create', compact(
            'sourceEnrollments',
            'academicClasses',
            'selectedSourceId'
        ));
    }

    /**
     * Perform a promotion through the dedicated service.
     */
    public function store(
        StoreStudentPromotionRequest $request,
        StudentPromotionService $promotionService
    ): RedirectResponse {
        Gate::authorize('create', StudentPromotion::class);

        $validated = $request->validated();

        $sourceEnrollment = StudentEnrollment::query()
            ->findOrFail($validated['source_enrollment_id']);

        $destinationClass = AcademicClass::query()
            ->findOrFail($validated['destination_class_id']);

        $promotion = $promotionService->promote(
            sourceEnrollment: $sourceEnrollment,
            destinationClass: $destinationClass,
            promotionType: $validated['promotion_type'],
            promotionDate: $validated['promotion_date'],
            notes: $validated['notes'] ?? null,
            actor: $request->user()
        );

        return to_route('student-promotions.show', $promotion)
            ->with(
                'success',
                'Student progression recorded successfully.'
            );
    }

    /**
     * Display an individual promotion record.
     */
    public function show(StudentPromotion $studentPromotion): View
    {
        Gate::authorize('view', $studentPromotion);

        $studentPromotion->load([
            'student',
            'sourceEnrollment.academicClass',
            'destinationEnrollment.academicClass',
            'promotedBy',
        ]);

        return view('student-promotions.show', compact(
            'studentPromotion'
        ));
    }
}
