<?php

namespace App\Admin\LocationGems\Controllers;

use App\Admin\LocationGems\Services\LocationGemExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationGemExportController extends Controller
{
    public function __construct(
        private readonly LocationGemExcelService $locationGemExcelService,
    ) {}

    /**
     * Download the Location Gems workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->locationGemExcelService->export();
    }
}
