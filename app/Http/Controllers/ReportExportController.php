<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\ApplicationSettingsService;
use App\Services\AuditLogger;
use App\Services\Reports\StudentAcademicReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReportExportController extends Controller
{
    public function studentPdf(
        Student $student,
        StudentAcademicReportService $reports,
        ApplicationSettingsService $settings,
        AuditLogger $auditLogger
    ): Response {
        if ($student->trashed()) {
            Gate::authorize('viewArchived', Student::class);
        } else {
            Gate::authorize('view', $student);
        }

        $data = $reports->forExport($student);

        $data['institutionName'] = $settings->get(
            'institution_name',
            config('app.name')
        );

        $data['generatedAt'] = now()->format('Y-m-d H:i');

        $filename = 'student-academic-report-'.$student->id.'.pdf';

        // Generate the document before recording the export.
        $pdfContent = Pdf::loadView(
            'reports.exports.student-pdf',
            $data
        )
            ->setPaper('a4', 'portrait')
            ->output();

        $auditLogger->record(
            event: 'reports.student.exported',
            auditable: $student,
            properties: [
                'format' => 'pdf',
                'report_type' => 'student_academic',
            ],
            actor: auth()->user()
        );

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
