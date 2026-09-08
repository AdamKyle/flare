<?php

namespace App\Admin\Classes\Controllers\Api;

use App\Admin\Classes\Requests\ClassImportRequest;
use App\Admin\Classes\Services\ClassExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassImportController extends Controller
{
    public function __construct(
        private readonly ClassExcelService $classExcelService,
    ) {}

    /**
     * Import validated Classes and return the success response.
     */
    public function __invoke(ClassImportRequest $request): JsonResponse
    {
        $this->classExcelService->import($request->file('classes_import'));

        return response()->json([
            'message' => 'Classes imported successfully.',
        ], 200);
    }
}
