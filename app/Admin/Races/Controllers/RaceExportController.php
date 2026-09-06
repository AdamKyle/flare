<?php

namespace App\Admin\Races\Controllers;

use App\Admin\Races\Services\RaceExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RaceExportController extends Controller
{
    /**
     * @param  RaceExcelService  $raceExcelService  Race Excel export/import service.
     */
    public function __construct(
        private readonly RaceExcelService $raceExcelService,
    ) {}

    /**
     * Download the Races workbook.
     *
     * @return BinaryFileResponse Races workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->raceExcelService->export();
    }
}
