<?php

namespace App\Admin\Npcs\Controllers;

use App\Admin\Npcs\Services\NpcExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NpcExportController extends Controller
{
    public function __construct(
        private readonly NpcExcelService $npcExcelService,
    ) {}

    /**
     * Download the NPCs workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->npcExcelService->export();
    }
}
