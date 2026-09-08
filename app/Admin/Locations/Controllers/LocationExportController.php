<?php

namespace App\Admin\Locations\Controllers;

use App\Admin\Locations\Services\LocationExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationExportController extends Controller
{
    public function __construct(
        private readonly LocationExcelService $locationExcelService,
    ) {}

    /**
     * Download the Locations workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->locationExcelService->export();
    }
}
