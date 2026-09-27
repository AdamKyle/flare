<?php

namespace App\Admin\Kingdoms\Controllers;

use App\Admin\Kingdoms\Services\KingdomExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KingdomExportController extends Controller
{
    /**
     * @param KingdomExcelService $kingdomExcelService
     */
    public function __construct(private readonly KingdomExcelService $kingdomExcelService) {}

    /**
     * Download the Kingdom workbook.
     *
     * @return BinaryFileResponse
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->kingdomExcelService->export();
    }
}
