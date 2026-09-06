<?php

namespace App\Admin\LocationGems\Controllers\Api;

use App\Admin\LocationGems\Requests\LocationGemImportRequest;
use App\Admin\LocationGems\Services\LocationGemExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocationGemImportController extends Controller
{
    /**
     * @param  LocationGemExcelService  $locationGemExcelService  Location Gem Excel export/import service.
     */
    public function __construct(
        private readonly LocationGemExcelService $locationGemExcelService,
    ) {}

    /**
     * Import validated Location Gem profiles and return the success response.
     *
     * @param  LocationGemImportRequest  $request  Validated Location Gem import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(LocationGemImportRequest $request): JsonResponse
    {
        $this->locationGemExcelService->import($request->file('location_gems_import'));

        return response()->json([
            'message' => 'Location Gems imported successfully.',
        ], 200);
    }
}
