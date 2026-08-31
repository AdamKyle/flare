<?php

namespace App\Admin\Quests\Controllers;

use App\Admin\Quests\Services\QuestExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestExportController extends Controller
{
    /**
     * @param  QuestExcelService  $questExcelService  Quest Excel export/import service.
     */
    public function __construct(
        private readonly QuestExcelService $questExcelService,
    ) {}

    /**
     * Download the Quests workbook.
     *
     * @return BinaryFileResponse Quests workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->questExcelService->export();
    }
}
