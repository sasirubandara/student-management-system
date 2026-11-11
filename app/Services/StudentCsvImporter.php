<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentCsvImporter
{
    /**
     * CSV columns accepted by the importer.
     */
    private const ALLOWED_COLUMNS = [
        'student_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'enrollment_date',
        'status',
    ];

    /**
     * Columns required in the CSV header.
     */
    private const REQUIRED_COLUMNS = [
        'student_number',
        'first_name',
        'last_name',
        'enrollment_date',
    ];

    /**
     * Import students from a CSV file.
     *
     * Valid rows are imported. Invalid and duplicate rows
     * are skipped and returned in the errors collection.
     *
     * @return array{
     *     imported: int,
     *     failed: int,
     *     errors: array<int, string>,
     *     file_error: ?string
     * }
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded CSV file could not be opened.',
            ]);
        }

        try {
            $headers = $this->readHeaders($handle);

            $imported = 0;
            $failed = 0;
            $processedRows = 0;
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $processedRows++;

                if (count($row) !== count($headers)) {
                    $failed++;

                    $errors[] = "Row {$rowNumber}: The number of columns does not match the CSV header.";

                    continue;
                }

                $data = $this->mapRow($headers, $row);

                $validator = Validator::make($data, [
                    'student_number' => [
                        'required',
                        'string',
                        'max:20',
                    ],
                    'first_name' => [
                        'required',
                        'string',
                        'max:100',
                    ],
                    'last_name' => [
                        'required',
                        'string',
                        'max:100',
                    ],
                    'email' => [
                        'nullable',
                        'email',
                        'max:255',
                    ],
                    'phone' => [
                        'nullable',
                        'string',
                        'max:30',
                    ],
                    'date_of_birth' => [
                        'nullable',
                        'date',
                        'before:today',
                    ],
                    'enrollment_date' => [
                        'required',
                        'date',
                    ],
                    'status' => [
                        'required',
                        'string',
                        'in:active,inactive',
                    ],
                ]);

                if ($validator->fails()) {
                    $failed++;

                    $messages = [];

                    foreach ($validator->errors()->all() as $message) {
                        $messages[] = $message;
                    }

                    $errors[] = "Row {$rowNumber}: "
                        . implode(' ', $messages);

                    continue;
                }

                $validated = $validator->validated();

                /*
                 * The students table has a unique student_number
                 * constraint. Check archived records as well.
                 */
                $duplicateExists = Student::withTrashed()
                    ->where(
                        'student_number',
                        $validated['student_number']
                    )
                    ->exists();

                if ($duplicateExists) {
                    $failed++;

                    $errors[] = "Row {$rowNumber}: Student number "
                        . $validated['student_number']
                        . ' already exists, including archived records.';

                    continue;
                }

                Student::create($validated);

                $imported++;
            }

            return [
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
                'file_error' => $processedRows === 0
                    ? 'The CSV file does not contain any student records.'
                    : null,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read and validate the CSV header.
     *
     * @param resource $handle
     * @return array<int, string>
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle, null, ',', '"', '');

        if ($headers === false || $headers === [null]) {
            throw ValidationException::withMessages([
                'file' => 'The CSV file is empty or has no header row.',
            ]);
        }

        $headers = array_map(
            fn ($header) => strtolower(trim((string) $header)),
            $headers
        );

        /*
         * Remove a UTF-8 byte order mark from the first header.
         */
        $headers[0] = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $headers[0]
        );

        if (in_array('', $headers, true)) {
            throw ValidationException::withMessages([
                'file' => 'The CSV header contains an empty column name.',
            ]);
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages([
                'file' => 'The CSV header contains duplicate column names.',
            ]);
        }

        $unknownColumns = array_diff(
            $headers,
            self::ALLOWED_COLUMNS
        );

        if ($unknownColumns !== []) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported CSV columns: '
                    . implode(', ', $unknownColumns),
            ]);
        }

        $missingColumns = array_diff(
            self::REQUIRED_COLUMNS,
            $headers
        );

        if ($missingColumns !== []) {
            throw ValidationException::withMessages([
                'file' => 'Missing required CSV columns: '
                    . implode(', ', $missingColumns),
            ]);
        }

        return $headers;
    }

    /**
     * Map a CSV row to the accepted student fields.
     *
     * @param array<int, string> $headers
     * @param array<int, string|null> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            $value = isset($row[$index])
                ? trim((string) $row[$index])
                : null;

            $data[$header] = $value === '' ? null : $value;
        }

        /*
         * Status is optional in the CSV. Default new records to active.
         */
        if (empty($data['status'])) {
            $data['status'] = Student::STATUS_ACTIVE;
        }

        return $data;
    }

    /**
     * Determine whether a CSV row is empty.
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}