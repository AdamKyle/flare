<?php

namespace App\Admin\Locations\Controllers;

use App\Admin\Locations\Services\LocationExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationExportController extends Controller
{
    /**
     * @param  LocationExcelService  $locationExcelService  Location Excel export/import service.
     */
    public function __construct(
        private readonly LocationExcelService $locationExcelService,
    ) {}

    /**
     * Download the Locations workbook.
     *
     * @return BinaryFileResponse Locations workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->locationExcelService->export();
    }
}
