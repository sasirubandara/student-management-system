<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\ExaminationReportExport;
use App\Exports\StudentAcademicReportExport;
use App\Models\AttendanceSession;
use App\Models\Examination;
use App\Models\Student;
use App\Services\ApplicationSettingsService;
use App\Services\AuditLogger;
use App\Services\Reports\AttendanceReportService;
use App\Services\Reports\ExaminationReportService;
use App\Services\Reports\ReportFilterService;
use App\Services\Reports\StudentAcademicReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * Export a filtered attendance report as PDF.
     */
    public function attendancePdf(
        Request $request,
        ReportFilterService $reportFilters,
        AttendanceReportService $attendanceReports,
        ApplicationSettingsService $settings,
        AuditLogger $auditLogger
    ): Response {
        Gate::authorize('viewAny', AttendanceSession::class);

        $filters = $reportFilters->validate($request);

        $filterLabels = $reportFilters->displayLabels($filters);

        $sessionsQuery = $attendanceReports->sessionsQuery($filters);

        $totalSessions = (clone $sessionsQuery)->count();

        $sessions = $sessionsQuery
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $attendanceSummary = $attendanceReports
            ->attendanceSummary($filters);

        $institutionName = $settings->get(
            'institution_name',
            config('app.name')
        );

        $generatedAt = now()->format('Y-m-d H:i');

        $pdfContent = Pdf::loadView(
            'reports.exports.attendance-pdf',
            compact(
                'sessions',
                'totalSessions',
                'attendanceSummary',
                'filters',
                'filterLabels',
                'institutionName',
                'generatedAt'
            )
        )
            ->setPaper('a4', 'landscape')
            ->output();

        $auditLogger->record(
            event: 'reports.attendance.exported',
            properties: [
                'format' => 'pdf',
                'report_type' => 'attendance',
                'filters_applied' => collect($filters)
                    ->contains(fn ($value) => $value !== null && $value !== ''),
            ],
            actor: auth()->user()
        );

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="attendance-report.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export a filtered examination report as PDF.
     */
    public function examinationsPdf(
        Request $request,
        ReportFilterService $reportFilters,
        ExaminationReportService $examinationReports,
        ApplicationSettingsService $settings,
        AuditLogger $auditLogger
    ): Response {
        Gate::authorize('viewAny', Examination::class);

        $filters = $reportFilters->validate($request);

        $filterLabels = $reportFilters->displayLabels($filters);

        $examinationsQuery = $examinationReports
            ->examinationsQuery($filters);

        $totalExaminations = (clone $examinationsQuery)->count();

        $examinations = $examinationsQuery
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();

        $gradeSummary = $examinationReports->gradeSummary($filters);

        $institutionName = $settings->get(
            'institution_name',
            config('app.name')
        );

        $generatedAt = now()->format('Y-m-d H:i');

        $pdfContent = Pdf::loadView(
            'reports.exports.examination-pdf',
            compact(
                'examinations',
                'totalExaminations',
                'gradeSummary',
                'filters',
                'filterLabels',
                'institutionName',
                'generatedAt'
            )
        )
            ->setPaper('a4', 'landscape')
            ->output();

        $auditLogger->record(
            event: 'reports.examinations.exported',
            properties: [
                'format' => 'pdf',
                'report_type' => 'examinations',
                'filters_applied' => collect($filters)
                    ->contains(fn ($value) => $value !== null && $value !== ''),
            ],
            actor: auth()->user()
        );

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="examination-report.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export a student's complete academic history as XLSX.
     */
    public function studentExcel(
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

        $content = Excel::raw(
            new StudentAcademicReportExport($data),
            ExcelWriter::XLSX
        );

        $auditLogger->record(
            event: 'reports.student.exported',
            auditable: $student,
            properties: [
                'format' => 'xlsx',
                'report_type' => 'student_academic',
            ],
            actor: auth()->user()
        );

        $filename = 'student-academic-report-'.$student->id.'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export filtered attendance records as an Excel workbook.
     */
    public function attendanceExcel(
        Request $request,
        ReportFilterService $reportFilters,
        AttendanceReportService $attendanceReports,
        ApplicationSettingsService $settings,
        AuditLogger $auditLogger
    ): Response {
        Gate::authorize('viewAny', AttendanceSession::class);

        $filters = $reportFilters->validate($request);

        $filterLabels = $reportFilters->displayLabels($filters);

        $sessionsQuery = $attendanceReports
            ->sessionsQuery($filters);

        $totalSessions = (clone $sessionsQuery)->count();

        $sessions = $sessionsQuery
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $attendanceSummary = $attendanceReports
            ->attendanceSummary($filters);

        $data = [
            'sessions' => $sessions,
            'totalSessions' => $totalSessions,
            'attendanceSummary' => $attendanceSummary,
            'filters' => $filters,
            'filterLabels' => $filterLabels,
            'institutionName' => $settings->get(
                'institution_name',
                config('app.name')
            ),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ];

        $content = Excel::raw(
            new AttendanceReportExport($data),
            ExcelWriter::XLSX
        );

        $auditLogger->record(
            event: 'reports.attendance.exported',
            properties: [
                'format' => 'xlsx',
                'report_type' => 'attendance',
                'filters_applied' => collect($filters)
                    ->contains(fn ($value) => $value !== null && $value !== ''),
            ],
            actor: auth()->user()
        );

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="attendance-report.xlsx"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export filtered examinations as an Excel workbook.
     */
    public function examinationsExcel(
        Request $request,
        ReportFilterService $reportFilters,
        ExaminationReportService $examinationReports,
        ApplicationSettingsService $settings,
        AuditLogger $auditLogger
    ): Response {
        Gate::authorize('viewAny', Examination::class);

        $filters = $reportFilters->validate($request);

        $filterLabels = $reportFilters->displayLabels($filters);

        $examinationsQuery = $examinationReports
            ->examinationsQuery($filters);

        $totalExaminations = (clone $examinationsQuery)->count();

        $examinations = $examinationsQuery
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();

        $gradeSummary = $examinationReports
            ->gradeSummary($filters);

        $data = [
            'examinations' => $examinations,
            'totalExaminations' => $totalExaminations,
            'gradeSummary' => $gradeSummary,
            'filters' => $filters,
            'filterLabels' => $filterLabels,
            'institutionName' => $settings->get(
                'institution_name',
                config('app.name')
            ),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ];

        $content = Excel::raw(
            new ExaminationReportExport($data),
            ExcelWriter::XLSX
        );

        $auditLogger->record(
            event: 'reports.examinations.exported',
            properties: [
                'format' => 'xlsx',
                'report_type' => 'examinations',
                'filters_applied' => collect($filters)
                    ->contains(
                        fn ($value) => $value !== null && $value !== ''
                    ),
            ],
            actor: auth()->user()
        );

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="examination-report.xlsx"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
