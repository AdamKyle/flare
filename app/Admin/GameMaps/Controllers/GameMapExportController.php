<?php

namespace App\Admin\GameMaps\Controllers;

use App\Admin\GameMaps\Services\GameMapExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GameMapExportController extends Controller
{
    /**
     * @param  GameMapExcelService  $gameMapExcelService  Game Map Excel export/import service.
     */
    public function __construct(
        private readonly GameMapExcelService $gameMapExcelService,
    ) {}

    /**
     * Download the Game Maps settings workbook.
     *
     * @return BinaryFileResponse Game Maps settings workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->gameMapExcelService->export();
    }
}
