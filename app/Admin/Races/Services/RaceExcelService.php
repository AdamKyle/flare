<?php

namespace App\Admin\Races\Services;

use App\Admin\Races\Exports\RacesExport;
use App\Admin\Races\Imports\RacesImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RaceExcelService
{
    /**
     * Download the Races workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new RacesExport, 'game_races.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Races from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new RacesImport, $file);
    }
}
