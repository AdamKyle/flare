<?php

namespace App\Admin\ClassMasteries\Services;

use App\Admin\ClassMasteries\Exports\ClassMasteriesExport;
use App\Admin\ClassMasteries\Imports\ClassMasteriesImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassMasteryExcelService
{
    /**
     * Download the Class Masteries workbook.
     *
     * @return BinaryFileResponse Class Masteries workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new ClassMasteriesExport, 'class-masteries.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Class Masteries from the validated workbook upload.
     *
     * @param  UploadedFile  $file  Uploaded Class Masteries workbook.
     * @return void Persists the imported Class Masteries.
     *
     * @codeCoverageIgnore
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new ClassMasteriesImport, $file);
    }
}
