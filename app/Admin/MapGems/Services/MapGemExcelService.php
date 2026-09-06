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
     * @return BinaryFileResponse Map Gems workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new MapGemsExport, 'map-gems.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Map Gem profiles from the validated workbook upload.
     *
     * @param  UploadedFile  $file  Uploaded Map Gems workbook.
     * @return void Persists the imported Map Gem profiles.
     *
     * @codeCoverageIgnore
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new MapGemsImport, $file);
    }
}
