<?php

namespace App\Admin\MapGems\Controllers;

use App\Admin\MapGems\Services\MapGemExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MapGemExportController extends Controller
{
    /**
     * @param  MapGemExcelService  $mapGemExcelService  Map Gem Excel export/import service.
     */
    public function __construct(
        private readonly MapGemExcelService $mapGemExcelService,
    ) {}

    /**
     * Download the Map Gems workbook.
     *
     * @return BinaryFileResponse Map Gems workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->mapGemExcelService->export();
    }
}
