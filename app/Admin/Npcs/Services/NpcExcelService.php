<?php

namespace App\Admin\Npcs\Services;

use App\Admin\Npcs\Exports\NpcsExport;
use App\Admin\Npcs\Imports\NpcsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NpcExcelService
{
    /**
     * Download the NPCs workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new NpcsExport, 'npcs.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import NPCs from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new NpcsImport, $file);
    }
}
