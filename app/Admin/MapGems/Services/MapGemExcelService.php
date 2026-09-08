<?php

namespace App\Admin\MapGems\Services;

use App\Admin\MapGems\Exports\MapGemsExport;
use App\Admin\MapGems\Imports\MapGemsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MapGemExcelService
{
    /**
     * Download the Map Gems workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new MapGemsExport, 'map-gems.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Map Gem profiles from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new MapGemsImport, $file);
    }
}
