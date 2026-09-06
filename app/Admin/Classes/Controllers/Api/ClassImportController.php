<?php

namespace App\Admin\Classes\Controllers\Api;

use App\Admin\Classes\Requests\ClassImportRequest;
use App\Admin\Classes\Services\ClassExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassImportController extends Controller
{
    /**
     * @param  ClassExcelService  $classExcelService  Class Excel export/import service.
     */
    public function __construct(
        private readonly ClassExcelService $classExcelService,
    ) {}

    /**
     * Import validated Classes and return the success response.
     *
     * @param  ClassImportRequest  $request  Validated Class import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(ClassImportRequest $request): JsonResponse
    {
        $this->classExcelService->import($request->file('classes_import'));

        return response()->json([
            'message' => 'Classes imported successfully.',
        ], 200);
    }
}
