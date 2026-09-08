<?php

namespace App\Admin\Locations\Controllers\Api;

use App\Admin\Locations\Requests\LocationImportRequest;
use App\Admin\Locations\Services\LocationExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocationImportController extends Controller
{
    public function __construct(
        private readonly LocationExcelService $locationExcelService,
    ) {}

    /**
     * Import validated Locations and return the success response.
     */
    public function __invoke(LocationImportRequest $request): JsonResponse
    {
        $this->locationExcelService->import($request->file('locations_import'));

        return response()->json([
            'message' => 'Locations imported successfully.',
        ], 200);
    }
}
