<?php

namespace App\Admin\PassiveSkills\Services;

use App\Admin\PassiveSkills\Exports\PassiveSkillsExport;
use App\Admin\PassiveSkills\Imports\PassiveSkillsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PassiveSkillExcelService
{
    /**
     * Download the Passive Skills workbook.
     *
     * @return BinaryFileResponse
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new PassiveSkillsExport, 'passive_skills.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Passive Skills from the validated workbook upload.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new PassiveSkillsImport, $file);
    }
}
