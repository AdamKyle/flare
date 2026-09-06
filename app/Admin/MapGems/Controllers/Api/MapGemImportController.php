<?php

namespace App\Admin\MapGems\Controllers\Api;

use App\Admin\MapGems\Requests\MapGemImportRequest;
use App\Admin\MapGems\Services\MapGemExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MapGemImportController extends Controller
{
    /**
     * @param  MapGemExcelService  $mapGemExcelService  Map Gem Excel export/import service.
     */
    public function __construct(
        private readonly MapGemExcelService $mapGemExcelService,
    ) {}

    /**
     * Import validated Map Gem profiles and return the success response.
     *
     * @param  MapGemImportRequest  $request  Validated Map Gem import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(MapGemImportRequest $request): JsonResponse
    {
        $this->mapGemExcelService->import($request->file('map_gems_import'));

        return response()->json([
            'message' => 'Map Gems imported successfully.',
        ], 200);
    }
}
