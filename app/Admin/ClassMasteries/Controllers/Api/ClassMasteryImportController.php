<?php

namespace App\Admin\ClassMasteries\Controllers\Api;

use App\Admin\ClassMasteries\Requests\ClassMasteryImportRequest;
use App\Admin\ClassMasteries\Services\ClassMasteryExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassMasteryImportController extends Controller
{
    /**
     * @param  ClassMasteryExcelService  $classMasteryExcelService  Class Mastery Excel export/import service.
     */
    public function __construct(
        private readonly ClassMasteryExcelService $classMasteryExcelService,
    ) {}

    /**
     * Import validated Class Masteries and return the success response.
     *
     * @param  ClassMasteryImportRequest  $request  Validated Class Mastery import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(ClassMasteryImportRequest $request): JsonResponse
    {
        $this->classMasteryExcelService->import($request->file('class_masteries_import'));

        return response()->json([
            'message' => 'Class Masteries imported successfully.',
        ], 200);
    }
}
