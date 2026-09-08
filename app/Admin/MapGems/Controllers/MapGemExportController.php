<?php

namespace App\Admin\MapGems\Controllers;

use App\Admin\MapGems\Services\MapGemExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MapGemExportController extends Controller
{
    public function __construct(
        private readonly MapGemExcelService $mapGemExcelService,
    ) {}

    /**
     * Download the Map Gems workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->mapGemExcelService->export();
    }
}
