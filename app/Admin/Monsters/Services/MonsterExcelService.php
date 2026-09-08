<?php

namespace App\Admin\Monsters\Services;

use App\Admin\Monsters\Exports\MonstersExport;
use App\Admin\Monsters\Imports\MonstersImport;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MonsterExcelService
{
    use ResponseBuilder;

    /**
     * Download the Monsters workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new MonstersExport, 'monsters.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Monsters from the validated workbook upload.
     */
    public function import(UploadedFile $file): array
    {
        $import = new MonstersImport;

        Excel::import($import, $file);

        if (! $import->wasSuccessful()) {
            return $this->errorResult($import->validationError() ?? 'Unable to import the Monsters workbook.');
        }

        return $this->successResult(['message' => 'Monsters imported successfully.']);
    }
}
