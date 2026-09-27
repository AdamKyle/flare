<?php

namespace App\Admin\Skills\Controllers;

use App\Admin\Skills\Services\SkillExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SkillExportController extends Controller
{
    /**
     * @param SkillExcelService $skillExcelService
     */
    public function __construct(private readonly SkillExcelService $skillExcelService) {}

    /**
     * Download the Skills workbook.
     *
     * @return BinaryFileResponse
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->skillExcelService->export();
    }
}
