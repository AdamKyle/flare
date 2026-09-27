<?php

namespace App\Admin\PassiveSkills\Controllers;

use App\Admin\PassiveSkills\Services\PassiveSkillExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PassiveSkillExportController extends Controller
{
    /**
     * @param PassiveSkillExcelService $passiveSkillExcelService
     */
    public function __construct(private readonly PassiveSkillExcelService $passiveSkillExcelService) {}

    /**
     * Download the Passive Skills workbook.
     *
     * @return BinaryFileResponse
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->passiveSkillExcelService->export();
    }
}
