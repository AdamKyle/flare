<?php

namespace App\Admin\Locations\Services;

use App\Admin\Locations\Exports\LocationsExport;
use App\Admin\Locations\Imports\LocationsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationExcelService
{
    /**
     * Download the Locations workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new LocationsExport, 'locations.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Locations from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new LocationsImport, $file);
    }
}
