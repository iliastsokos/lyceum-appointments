<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BuildsTestSpreadsheets
{
    /**
     * Build a real, parseable .xlsx UploadedFile fixture from a header row
     * and data rows, so import tests exercise the actual PhpSpreadsheet
     * reading path rather than a fake/empty file.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function makeXlsxUpload(array $headers, array $rows, string $filename = 'import.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        (new Xlsx($spreadsheet))->save($tempPath);

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return UploadedFile::fake()->createWithContent($filename, $content);
    }
}
