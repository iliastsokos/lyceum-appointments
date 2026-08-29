<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImportType;
use App\Exceptions\UnreadableSpreadsheetException;
use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(private readonly ExcelImportService $importService) {}

    public function show(string $type): View
    {
        $importType = $this->resolveType($type);

        $recentBatches = ImportBatch::where('import_type', $importType)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.imports.upload', [
            'type' => $importType,
            'recentBatches' => $recentBatches,
        ]);
    }

    public function preview(Request $request, string $type): View|RedirectResponse
    {
        $importType = $this->resolveType($type);

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx', 'max:5120'],
        ]);

        $file = $request->file('file');
        $storagePath = $this->importService->storeUpload($file);

        try {
            ['headers' => $headers, 'rows' => $rows] = $this->importService->readRows($storagePath);
        } catch (UnreadableSpreadsheetException $e) {
            $this->importService->deleteUpload($storagePath);

            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $missing = $this->importService->missingHeaders($headers, $importType);

        if ($missing !== []) {
            $this->importService->deleteUpload($storagePath);

            return back()->withErrors([
                'file' => 'This file is missing required column(s): '.implode(', ', $missing),
            ]);
        }

        if ($rows->isEmpty()) {
            $this->importService->deleteUpload($storagePath);

            return back()->withErrors(['file' => 'This file has no data rows.']);
        }

        $result = $importType === ImportType::Teachers
            ? $this->importService->validateTeacherRows($rows)
            : $this->importService->validateGuardianRows($rows);

        $token = (string) Str::uuid();
        Session::put("import_pending.{$token}", [
            'path' => $storagePath,
            'type' => $importType->value,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        return view('admin.imports.preview', [
            'type' => $importType,
            'token' => $token,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
        ]);
    }

    public function commit(Request $request, string $type): View|RedirectResponse
    {
        $importType = $this->resolveType($type);

        $request->validate(['token' => ['required', 'uuid']]);

        $pending = Session::get("import_pending.{$request->string('token')}");

        if (! $pending || $pending['type'] !== $importType->value) {
            return redirect()->route('admin.imports.show', $importType->value)
                ->withErrors(['file' => 'This import preview has expired. Please upload the file again.']);
        }

        try {
            ['headers' => $headers, 'rows' => $rows] = $this->importService->readRows($pending['path']);
        } catch (UnreadableSpreadsheetException $e) {
            $this->importService->deleteUpload($pending['path']);
            Session::forget("import_pending.{$request->string('token')}");

            return redirect()->route('admin.imports.show', $importType->value)
                ->withErrors(['file' => $e->getMessage()]);
        }

        $missing = $this->importService->missingHeaders($headers, $importType);

        if ($missing !== [] || $rows->isEmpty()) {
            $this->importService->deleteUpload($pending['path']);
            Session::forget("import_pending.{$request->string('token')}");

            return redirect()->route('admin.imports.show', $importType->value)
                ->withErrors(['file' => 'This file could no longer be read. Please upload it again.']);
        }

        $result = $importType === ImportType::Teachers
            ? $this->importService->validateTeacherRows($rows)
            : $this->importService->validateGuardianRows($rows);

        $batch = $importType === ImportType::Teachers
            ? $this->importService->commitTeachers($result['rows'], $request->user(), $pending['original_filename'])
            : $this->importService->commitGuardians($result['rows'], $request->user(), $pending['original_filename']);

        $this->importService->deleteUpload($pending['path']);
        Session::forget("import_pending.{$request->string('token')}");

        $credentials = $batch->credentials ?? [];
        if ($credentials !== []) {
            Session::put("import_credentials.{$batch->id}", $credentials);
        }

        return view('admin.imports.result', [
            'type' => $importType,
            'batch' => $batch,
            'hasCredentials' => $credentials !== [],
        ]);
    }

    public function downloadCredentials(Request $request, ImportBatch $batch): StreamedResponse
    {
        $credentials = Session::get("import_credentials.{$batch->id}", []);

        abort_if($credentials === [], 404);

        Session::forget("import_credentials.{$batch->id}");

        return response()->streamDownload(function () use ($credentials) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'temporary_password']);
            foreach ($credentials as $row) {
                fputcsv($handle, [$this->csvSafe($row['email']), $this->csvSafe($row['password'])]);
            }
            fclose($handle);
        }, "import-{$batch->id}-credentials.csv", ['Content-Type' => 'text/csv']);
    }

    public function history(Request $request): View
    {
        $batches = ImportBatch::with('admin')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.imports.history', ['batches' => $batches]);
    }

    public function historyShow(ImportBatch $batch): View
    {
        $errors = $batch->errors()->orderBy('row_number')->paginate(50);

        return view('admin.imports.history-show', ['batch' => $batch, 'errors' => $errors]);
    }

    public function downloadErrorReport(ImportBatch $batch): StreamedResponse
    {
        $errors = $batch->errors()->orderBy('row_number')->get();

        return response()->streamDownload(function () use ($errors) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['row', 'field', 'error', 'data']);
            foreach ($errors as $error) {
                fputcsv($handle, [
                    $error->row_number,
                    $this->csvSafe($error->field),
                    $this->csvSafe($error->error_message),
                    $this->csvSafe(json_encode($error->row_data)),
                ]);
            }
            fclose($handle);
        }, "import-{$batch->id}-errors.csv", ['Content-Type' => 'text/csv']);
    }

    public function teacherTemplate(): StreamedResponse
    {
        return $this->templateDownload(
            ['first_name', 'last_name', 'email', 'role', 'subject'],
            [['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics']],
            'teacher-import-template.xlsx'
        );
    }

    public function guardianTemplate(): StreamedResponse
    {
        return $this->templateDownload(
            ['guardian_first_name', 'guardian_last_name', 'guardian_email', 'child_first_name', 'child_last_name', 'child_class'],
            [
                ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'B1'],
                ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Nikos', 'Papadopoulos', 'G2'],
            ],
            'guardian-import-template.xlsx'
        );
    }

    private function templateDownload(array $headers, array $sampleRows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($sampleRows, null, 'A2');
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function resolveType(string $type): ImportType
    {
        $resolved = ImportType::tryFrom($type);
        abort_if($resolved === null, 404);

        return $resolved;
    }

    /**
     * Prevent CSV/spreadsheet formula injection: a value opened in Excel
     * that starts with =, +, -, or @ can be interpreted as a formula.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains('=+-@', $value[0])) {
            return "'".$value;
        }

        return $value;
    }
}
