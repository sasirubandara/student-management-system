<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicationSettingRequest;
use App\Models\AcademicYear;
use App\Models\ApplicationSetting;
use App\Services\ApplicationSettingsService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    /**
     * Display the application settings form.
     */
    public function edit(
        ApplicationSettingsService $settingsService
    ): View {
        Gate::authorize('viewAny', ApplicationSetting::class);

        $academicYears = AcademicYear::query()
            ->orderByDesc('year')
            ->get(['id', 'year', 'name', 'start_date', 'end_date']);

        return view('settings.edit', [
            'settings' => $settingsService->all(),
            'timezones' => DateTimeZone::listIdentifiers(
                DateTimeZone::ALL
            ),
            'dateFormats' => ApplicationSetting::DATE_FORMATS,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the application settings.
     */
    public function update(
        UpdateApplicationSettingRequest $request,
        ApplicationSettingsService $settingsService
    ): RedirectResponse {
        $settingsService->update(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('settings.edit')
            ->with(
                'success',
                'Application settings updated successfully.'
            );
    }
}
