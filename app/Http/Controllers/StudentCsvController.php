<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\StudentCsvImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentCsvController extends Controller
{
    /**
     * Display the CSV import form.
     */
    public function createImport(): View
    {
        Gate::authorize('create', Student::class);

        return view('students.import');
    }

    /**
     * Import students from an uploaded CSV file.
     */
    public function importCsv(
        Request $request,
        StudentCsvImporter $importer,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ]);

        $result = $importer->import($validated['file']);

        // Record only the import summary, never CSV contents or row data.
        $auditLogger->record(
            'student.csv_import_processed',
            properties: [
                'imported_rows' => (int) ($result['imported'] ?? 0),
                'failed_rows' => (int) ($result['failed'] ?? 0),
                'has_errors' => (
                    (int) ($result['failed'] ?? 0) > 0
                    || ! empty($result['file_error'])
                    || ! empty($result['errors'])
                ),
            ]
        );

        $response = redirect()
            ->route('students.import.create')
            ->with('import_result', $result);

        if ($result['imported'] > 0) {
            $response->with(
                'success',
                $result['imported'] . ' student record(s) imported successfully.'
            );
        }

        return $response;
    }

    /**
     * Export active student records to CSV.
     *
     * The current student search query is preserved.
     */
    public function export(
        Request $request,
        AuditLogger $auditLogger
    ): StreamedResponse {
        Gate::authorize('viewAny', Student::class);

        $search = trim((string) $request->query('search', ''));

        $students = Student::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('student_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('id');

        // Count the records included in this export without retrieving them.
        $recordsCount = (clone $students)->count();

        // Record that the export was requested.
        $auditLogger->record(
            'student.csv_export_requested',
            properties: [
                'records_count' => $recordsCount,
                'search_filter_applied' => $search !== '',
            ]
        );

        $filename = 'students-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(
            function () use ($students): void {
                $output = fopen('php://output', 'w');

                if ($output === false) {
                    return;
                }

                /*
                 * UTF-8 BOM improves compatibility with spreadsheet
                 * applications when opening the CSV file.
                 */
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'student_number',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'date_of_birth',
                    'enrollment_date',
                    'status',
                ], ',', '"', '');

                $students->chunk(500, function ($records) use ($output): void {
                    foreach ($records as $student) {
                        fputcsv($output, [
                            $this->safeCsvCell($student->student_number),
                            $this->safeCsvCell($student->first_name),
                            $this->safeCsvCell($student->last_name),
                            $this->safeCsvCell($student->email),
                            $this->safeCsvCell($student->phone),
                            $this->safeCsvCell($student->date_of_birth),
                            $this->safeCsvCell($student->enrollment_date),
                            $this->safeCsvCell($student->status),
                        ], ',', '"', '');
                    }
                });

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    /**
     * Download a blank CSV template.
     */
    public function downloadTemplate(
        AuditLogger $auditLogger
    ): StreamedResponse {
        Gate::authorize('create', Student::class);

        $auditLogger->record(
            'student.csv_template_download_requested'
        );

        return response()->streamDownload(
            function (): void {
                $output = fopen('php://output', 'w');

                if ($output === false) {
                    return;
                }

                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'student_number',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'date_of_birth',
                    'enrollment_date',
                    'status',
                ], ',', '"', '');

                fclose($output);
            },
            'student-import-template.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    /**
     * Protect exported text from spreadsheet formula injection.
     */
    private function safeCsvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        $trimmed = ltrim($value, " \t\n\r\0\x0B");

        if (
            preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1
        ) {
            /*
             * Preserve ordinary phone numbers such as +94771234567.
             */
            $isPhoneNumber = preg_match(
                '/^\+[\d\s().-]+$/D',
                $trimmed
            ) === 1;

            /*
             * Preserve ordinary negative numeric values.
             */
            $isNegativeNumber = preg_match(
                '/^-\d+(?:\.\d+)?$/D',
                $trimmed
            ) === 1;

            if (! $isPhoneNumber && ! $isNegativeNumber) {
                return "'" . $value;
            }
        }

        return $value;
    }
}