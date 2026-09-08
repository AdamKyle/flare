<?php

namespace App\Admin\LocationGems\Services;

use App\Admin\LocationGems\Exports\LocationGemsExport;
use App\Admin\LocationGems\Imports\LocationGemsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationGemExcelService
{
    /**
     * Download the Location Gems workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new LocationGemsExport, 'location-gems.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Location Gem profiles from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new LocationGemsImport, $file);
    }
}
