<?php

namespace App\Services;

use App\Enums\ImportType;
use App\Enums\SchoolClass;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\UnreadableSpreadsheetException;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    private const MAX_ROWS = 2000;

    private const TEACHER_HEADERS = ['first_name', 'last_name', 'email', 'role', 'subject'];

    private const GUARDIAN_HEADERS = ['guardian_first_name', 'guardian_last_name', 'guardian_email', 'child_first_name', 'child_last_name', 'child_class'];

    public function __construct(private readonly AccountProvisioningService $provisioning) {}

    /**
     * Store an uploaded file outside the public webroot and return its
     * storage-relative path. Never trust the file's declared MIME type or
     * extension beyond what Laravel's own upload validation already checked.
     */
    public function storeUpload(UploadedFile $file): string
    {
        return $file->store('imports/pending', 'local');
    }

    public function deleteUpload(string $storagePath): void
    {
        Storage::disk('local')->delete($storagePath);
    }

    /**
     * Parse the workbook into row arrays. Formulas are deliberately never
     * calculated — a cell containing "=SOMETHING()" is read as that literal
     * string, so it simply fails normal field validation instead of being
     * evaluated (spec §25: never execute formulas/macros on import).
     *
     * @return array{headers: array<int, string>, rows: Collection<int, array{row_number: int, raw: array<string, string>}>}
     */
    public function readRows(string $storagePath): array
    {
        $fullPath = Storage::disk('local')->path($storagePath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
        } catch (\Throwable $e) {
            // A file can pass extension/MIME validation and still be an
            // unreadable or corrupted workbook (or not a spreadsheet at
            // all, despite the .xlsx name). Never let a parser exception
            // bubble into a raw 500 — treat it as "not a valid file."
            Log::warning('Import file could not be parsed', ['path' => $storagePath, 'exception' => $e->getMessage()]);

            throw new UnreadableSpreadsheetException;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, false, false, false);

        if ($data === []) {
            return ['headers' => [], 'rows' => collect()];
        }

        $headerRow = array_shift($data);
        $headers = array_map(
            fn ($h) => Str::of((string) $h)->trim()->lower()->replace(' ', '_')->toString(),
            $headerRow
        );

        $rows = collect($data)
            ->filter(fn ($row) => collect($row)->contains(fn ($v) => $v !== null && trim((string) $v) !== ''))
            ->values()
            ->take(self::MAX_ROWS)
            ->map(function ($row, $index) use ($headers) {
                $raw = [];
                foreach ($headers as $i => $key) {
                    $raw[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
                }

                // Header row is row 1 in the spreadsheet, so the first data row is row 2.
                return ['row_number' => $index + 2, 'raw' => $raw];
            });

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array<int, string> missing required headers, empty if all present
     */
    public function missingHeaders(array $headers, ImportType $type): array
    {
        $required = $type === ImportType::Teachers ? self::TEACHER_HEADERS : self::GUARDIAN_HEADERS;

        return array_values(array_diff($required, $headers));
    }

    /**
     * @param  Collection<int, array{row_number: int, raw: array<string, string>}>  $rows
     * @return array{rows: Collection<int, array>, summary: array<string, int>}
     */
    public function validateTeacherRows(Collection $rows): array
    {
        $seenEmails = [];

        $validated = $rows->map(function (array $item) use (&$seenEmails) {
            $raw = $item['raw'];
            $errors = [];

            $firstName = $raw['first_name'] ?? '';
            $lastName = $raw['last_name'] ?? '';
            $email = Str::lower($raw['email'] ?? '');
            $role = Str::lower($raw['role'] ?? '');
            $subject = $raw['subject'] ?? '';

            if ($firstName === '') {
                $errors['first_name'] = 'Υποχρεωτικό';
            }
            if ($lastName === '') {
                $errors['last_name'] = 'Υποχρεωτικό';
            }

            if ($email === '') {
                $errors['email'] = 'Υποχρεωτικό';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Μη έγκυρο email';
            } elseif (isset($seenEmails[$email])) {
                $errors['email'] = 'Διπλότυπο';
            } else {
                $seenEmails[$email] = true;
            }

            if ($role === '') {
                $errors['role'] = 'Υποχρεωτικό';
            } elseif ($role !== 'teacher') {
                $errors['role'] = 'Μη έγκυρος ρόλος';
            }

            if ($subject === '') {
                $errors['subject'] = 'Υποχρεωτικό';
            }

            return [
                'row_number' => $item['row_number'],
                'data' => compact('firstName', 'lastName', 'email', 'subject'),
                'errors' => $errors,
                'status' => $errors === [] ? 'pending' : 'error',
                'skip_reason' => null,
            ];
        });

        $candidateEmails = $validated->where('status', 'pending')->pluck('data.email')->all();
        $existingEmails = $this->existingEmails($candidateEmails);

        $validated = $validated->map(function (array $row) use ($existingEmails) {
            if ($row['status'] === 'pending') {
                if (isset($existingEmails[$row['data']['email']])) {
                    $row['status'] = 'skip';
                    $row['skip_reason'] = 'Υπάρχων λογαριασμός';
                } else {
                    $row['status'] = 'valid';
                }
            }

            return $row;
        });

        return ['rows' => $validated, 'summary' => $this->summarize($validated)];
    }

    /**
     * @param  Collection<int, array{row_number: int, raw: array<string, string>}>  $rows
     * @return array{rows: Collection<int, array>, summary: array<string, int>}
     */
    public function validateGuardianRows(Collection $rows): array
    {
        $validClasses = SchoolClass::values();

        $validated = $rows->map(function (array $item) use ($validClasses) {
            $raw = $item['raw'];
            $errors = [];

            $guardianFirstName = $raw['guardian_first_name'] ?? '';
            $guardianLastName = $raw['guardian_last_name'] ?? '';
            $guardianEmail = Str::lower($raw['guardian_email'] ?? '');
            $childFirstName = $raw['child_first_name'] ?? '';
            $childLastName = $raw['child_last_name'] ?? '';
            $childClass = Str::upper($raw['child_class'] ?? '');

            if ($guardianFirstName === '') {
                $errors['guardian_first_name'] = 'Υποχρεωτικό';
            }
            if ($guardianLastName === '') {
                $errors['guardian_last_name'] = 'Υποχρεωτικό';
            }

            if ($guardianEmail === '') {
                $errors['guardian_email'] = 'Υποχρεωτικό';
            } elseif (! filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['guardian_email'] = 'Μη έγκυρο email';
            }

            if ($childFirstName === '') {
                $errors['child_first_name'] = 'Υποχρεωτικό';
            }
            if ($childLastName === '') {
                $errors['child_last_name'] = 'Υποχρεωτικό';
            }

            if ($childClass === '') {
                $errors['child_class'] = 'Υποχρεωτικό';
            } elseif (! in_array($childClass, $validClasses, true)) {
                $errors['child_class'] = 'Μη έγκυρη τάξη';
            }

            return [
                'row_number' => $item['row_number'],
                'data' => compact('guardianFirstName', 'guardianLastName', 'guardianEmail', 'childFirstName', 'childLastName', 'childClass'),
                'errors' => $errors,
                'status' => $errors === [] ? 'pending' : 'error',
                'skip_reason' => null,
            ];
        });

        $candidateEmails = $validated->where('status', 'pending')->pluck('data.guardianEmail')->unique()->values()->all();
        $existingEmails = $this->existingEmails($candidateEmails);

        $validated = $validated->map(function (array $row) use ($existingEmails) {
            if ($row['status'] === 'pending') {
                if (isset($existingEmails[$row['data']['guardianEmail']])) {
                    $row['status'] = 'skip';
                    $row['skip_reason'] = 'Υπάρχων λογαριασμός κηδεμόνα';
                } else {
                    $row['status'] = 'valid';
                }
            }

            return $row;
        });

        $summary = $this->summarize($validated);
        $summary['guardians_new'] = $validated->where('status', 'valid')->pluck('data.guardianEmail')->unique()->count();
        $summary['guardians_existing'] = $validated->where('status', 'skip')->pluck('data.guardianEmail')->unique()->count();

        return ['rows' => $validated, 'summary' => $summary];
    }

    /**
     * @param  Collection<int, array>  $validatedRows
     */
    public function commitTeachers(Collection $validatedRows, User $admin, string $originalFilename): ImportBatch
    {
        $batch = ImportBatch::create([
            'admin_id' => $admin->id,
            'filename' => $originalFilename,
            'import_type' => ImportType::Teachers,
            'total_rows' => $validatedRows->count(),
            'successful_rows' => 0,
            'failed_rows' => 0,
            'skipped_rows' => 0,
        ]);

        $created = 0;
        $failed = 0;
        $skipped = 0;
        $credentials = [];

        foreach ($validatedRows as $row) {
            if ($row['status'] === 'error') {
                $failed++;
                $this->recordRowErrors($batch, $row);

                continue;
            }

            if ($row['status'] === 'skip') {
                $skipped++;
                $this->recordRowSkip($batch, $row, 'email');

                continue;
            }

            try {
                $temporaryPassword = $this->provisioning->generateTemporaryPassword();

                $teacher = DB::transaction(function () use ($row, $temporaryPassword) {
                    return User::create([
                        'role' => UserRole::Teacher,
                        'first_name' => $row['data']['firstName'],
                        'last_name' => $row['data']['lastName'],
                        'email' => $row['data']['email'],
                        'subject' => $row['data']['subject'],
                        'password' => Hash::make($temporaryPassword),
                        'status' => UserStatus::Active,
                        'must_change_password' => true,
                    ]);
                });

                $created++;
                $credentials[] = ['email' => $teacher->email, 'password' => $temporaryPassword];
            } catch (\Throwable $e) {
                Log::error('Teacher import row failed', ['row' => $row['row_number'], 'exception' => $e->getMessage()]);
                $failed++;
                ImportError::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $row['row_number'],
                    'field' => 'email',
                    'error_message' => 'This account could not be created. Please try importing this row again.',
                    'row_data' => $row['data'],
                ]);
            }
        }

        $batch->update(['successful_rows' => $created, 'failed_rows' => $failed, 'skipped_rows' => $skipped]);

        return $batch->fresh()->setAttribute('credentials', $credentials);
    }

    /**
     * @param  Collection<int, array>  $validatedRows
     */
    public function commitGuardians(Collection $validatedRows, User $admin, string $originalFilename): ImportBatch
    {
        $batch = ImportBatch::create([
            'admin_id' => $admin->id,
            'filename' => $originalFilename,
            'import_type' => ImportType::Guardians,
            'total_rows' => $validatedRows->count(),
            'successful_rows' => 0,
            'failed_rows' => 0,
            'skipped_rows' => 0,
        ]);

        $childrenCreated = 0;
        $failedRows = 0;
        $skippedRows = 0;
        $credentials = [];

        foreach ($validatedRows->where('status', 'error') as $row) {
            $failedRows++;
            $this->recordRowErrors($batch, $row);
        }

        foreach ($validatedRows->where('status', 'skip') as $row) {
            $skippedRows++;
            $this->recordRowSkip($batch, $row, 'guardian_email');
        }

        $groups = $validatedRows->where('status', 'valid')->groupBy('data.guardianEmail');

        foreach ($groups as $email => $rowsForGuardian) {
            try {
                $temporaryPassword = $this->provisioning->generateTemporaryPassword();
                $first = $rowsForGuardian->first();

                $guardian = DB::transaction(function () use ($rowsForGuardian, $first, $email, $temporaryPassword) {
                    $guardian = User::create([
                        'role' => UserRole::Guardian,
                        'first_name' => $first['data']['guardianFirstName'],
                        'last_name' => $first['data']['guardianLastName'],
                        'email' => $email,
                        'password' => Hash::make($temporaryPassword),
                        'status' => UserStatus::Active,
                        'must_change_password' => true,
                    ]);

                    foreach ($rowsForGuardian as $row) {
                        $guardian->children()->create([
                            'first_name' => $row['data']['childFirstName'],
                            'last_name' => $row['data']['childLastName'],
                            'class' => $row['data']['childClass'],
                        ]);
                    }

                    return $guardian;
                });

                $childrenCreated += $rowsForGuardian->count();
                $credentials[] = ['email' => $guardian->email, 'password' => $temporaryPassword];
            } catch (\Throwable $e) {
                Log::error('Guardian import group failed', ['email' => $email, 'exception' => $e->getMessage()]);
                $failedRows += $rowsForGuardian->count();

                foreach ($rowsForGuardian as $row) {
                    ImportError::create([
                        'import_batch_id' => $batch->id,
                        'row_number' => $row['row_number'],
                        'field' => 'guardian_email',
                        'error_message' => 'This account could not be created. Please try importing this row again.',
                        'row_data' => $row['data'],
                    ]);
                }
            }
        }

        $batch->update(['successful_rows' => $childrenCreated, 'failed_rows' => $failedRows, 'skipped_rows' => $skippedRows]);

        return $batch->fresh()->setAttribute('credentials', $credentials);
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<string, bool> keyed by lowercased email
     */
    private function existingEmails(array $emails): array
    {
        if ($emails === []) {
            return [];
        }

        return User::whereIn('email', $emails)
            ->pluck('email')
            ->map(fn ($e) => Str::lower($e))
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    private function recordRowErrors(ImportBatch $batch, array $row): void
    {
        foreach ($row['errors'] as $field => $message) {
            ImportError::create([
                'import_batch_id' => $batch->id,
                'row_number' => $row['row_number'],
                'field' => $field,
                'error_message' => $message,
                'row_data' => $row['data'],
            ]);
        }
    }

    private function recordRowSkip(ImportBatch $batch, array $row, string $field): void
    {
        ImportError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $row['row_number'],
            'field' => $field,
            'error_message' => $row['skip_reason'],
            'row_data' => $row['data'],
        ]);
    }

    /**
     * @param  Collection<int, array>  $rows
     * @return array<string, int>
     */
    private function summarize(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'valid' => $rows->where('status', 'valid')->count(),
            'skip' => $rows->where('status', 'skip')->count(),
            'error' => $rows->where('status', 'error')->count(),
        ];
    }
}
