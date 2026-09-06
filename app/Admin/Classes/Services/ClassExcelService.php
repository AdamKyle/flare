<?php

namespace App\Admin\Classes\Services;

use App\Admin\Classes\Exports\ClassesExport;
use App\Admin\Classes\Imports\ClassesImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassExcelService
{
    /**
     * Download the Classes workbook.
     *
     * @return BinaryFileResponse Classes workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new ClassesExport, 'game_classes.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Classes from the validated workbook upload.
     *
     * @param  UploadedFile  $file  Uploaded Classes workbook.
     * @return void Persists the imported Classes.
     *
     * @codeCoverageIgnore
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new ClassesImport, $file);
    }
}
