<?php

namespace App\Admin\GameMaps\Services;

use App\Admin\GameMaps\Exports\GameMapsExport;
use App\Admin\GameMaps\Imports\GameMapsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GameMapExcelService
{
    /**
     * Download the Game Maps settings workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new GameMapsExport, 'game-maps.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Game Map settings from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new GameMapsImport, $file);
    }
}
