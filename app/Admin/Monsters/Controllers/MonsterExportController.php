<?php

namespace App\Admin\Monsters\Controllers;

use App\Admin\Monsters\Services\MonsterExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MonsterExportController extends Controller
{
    public function __construct(
        private readonly MonsterExcelService $monsterExcelService,
    ) {}

    /**
     * Download the Monsters workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->monsterExcelService->export();
    }
}
