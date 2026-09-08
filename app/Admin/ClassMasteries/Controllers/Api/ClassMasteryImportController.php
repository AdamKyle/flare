<?php

namespace App\Admin\ClassMasteries\Controllers\Api;

use App\Admin\ClassMasteries\Requests\ClassMasteryImportRequest;
use App\Admin\ClassMasteries\Services\ClassMasteryExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassMasteryImportController extends Controller
{
    public function __construct(
        private readonly ClassMasteryExcelService $classMasteryExcelService,
    ) {}

    /**
     * Import validated Class Masteries and return the success response.
     */
    public function __invoke(ClassMasteryImportRequest $request): JsonResponse
    {
        $this->classMasteryExcelService->import($request->file('class_masteries_import'));

        return response()->json([
            'message' => 'Class Masteries imported successfully.',
        ], 200);
    }
}
