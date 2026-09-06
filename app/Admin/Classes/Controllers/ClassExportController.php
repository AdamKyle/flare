<?php

namespace App\Admin\Classes\Controllers;

use App\Admin\Classes\Services\ClassExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassExportController extends Controller
{
    /**
     * @param  ClassExcelService  $classExcelService  Class Excel export/import service.
     */
    public function __construct(
        private readonly ClassExcelService $classExcelService,
    ) {}

    /**
     * Download the Classes workbook.
     *
     * @return BinaryFileResponse Classes workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->classExcelService->export();
    }
}
