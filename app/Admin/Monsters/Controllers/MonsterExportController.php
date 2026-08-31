<?php

namespace App\Admin\Monsters\Controllers;

use App\Admin\Monsters\Services\MonsterExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MonsterExportController extends Controller
{
    /**
     * @param  MonsterExcelService  $monsterExcelService  Monster Excel export/import service.
     */
    public function __construct(
        private readonly MonsterExcelService $monsterExcelService,
    ) {}

    /**
     * Download the Monsters workbook.
     *
     * @return BinaryFileResponse Monsters workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->monsterExcelService->export();
    }
}
