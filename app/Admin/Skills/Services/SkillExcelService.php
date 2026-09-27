<?php

namespace App\Admin\Skills\Services;

use App\Admin\Services\AssignSkillService;
use App\Admin\Skills\Exports\SkillsExport;
use App\Admin\Skills\Imports\SkillsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SkillExcelService
{
    /**
     * @param AssignSkillService $assignSkillService
     */
    public function __construct(private readonly AssignSkillService $assignSkillService) {}

    /**
     * Download the Skills workbook.
     *
     * @return BinaryFileResponse
     *
     * @codeCoverageIgnore
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new SkillsExport, 'skills.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import Skills from the validated workbook upload and assign any new Skills to existing Characters.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new SkillsImport, $file, null, ExcelWriter::XLSX);

        $this->assignSkillService->assignSkills();
    }
}
