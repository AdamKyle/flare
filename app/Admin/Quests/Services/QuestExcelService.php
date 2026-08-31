<?php

namespace App\Admin\Quests\Services;

use App\Admin\Quests\Exports\QuestsExport;
use App\Admin\Quests\Imports\QuestsImport;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestExcelService
{
    use ResponseBuilder;

    /**
     * Download the Quests workbook.
     *
     * @return BinaryFileResponse Quests workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new QuestsExport, 'quests.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Quests from the validated workbook upload.
     *
     * @param  UploadedFile  $file  Uploaded Quests workbook.
     * @return array Import result: a success result, or an error result describing the exact validation failure.
     */
    public function import(UploadedFile $file): array
    {
        $import = new QuestsImport;

        Excel::import($import, $file);

        if (! $import->wasSuccessful()) {
            return $this->errorResult($import->validationError() ?? 'Unable to import the Quests workbook.');
        }

        return $this->successResult(['message' => 'Quests imported successfully.']);
    }
}
