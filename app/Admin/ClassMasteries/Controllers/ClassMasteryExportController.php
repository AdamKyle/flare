<?php

namespace App\Admin\ClassMasteries\Controllers;

use App\Admin\ClassMasteries\Services\ClassMasteryExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassMasteryExportController extends Controller
{
    /**
     * @param  ClassMasteryExcelService  $classMasteryExcelService  Class Mastery Excel export/import service.
     */
    public function __construct(
        private readonly ClassMasteryExcelService $classMasteryExcelService,
    ) {}

    /**
     * Download the Class Masteries workbook.
     *
     * @return BinaryFileResponse Class Masteries workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(): BinaryFileResponse
    {
        return $this->classMasteryExcelService->export();
    }
}
