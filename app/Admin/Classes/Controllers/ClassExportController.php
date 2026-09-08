<?php

namespace App\Admin\Classes\Controllers;

use App\Admin\Classes\Services\ClassExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassExportController extends Controller
{
    public function __construct(
        private readonly ClassExcelService $classExcelService,
    ) {}

    /**
     * Download the Classes workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->classExcelService->export();
    }
}
