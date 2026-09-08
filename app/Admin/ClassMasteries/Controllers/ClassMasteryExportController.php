<?php

namespace App\Admin\ClassMasteries\Controllers;

use App\Admin\ClassMasteries\Services\ClassMasteryExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassMasteryExportController extends Controller
{
    public function __construct(
        private readonly ClassMasteryExcelService $classMasteryExcelService,
    ) {}

    /**
     * Download the Class Masteries workbook.
     *
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->classMasteryExcelService->export();
    }
}
