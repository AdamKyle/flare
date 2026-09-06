<?php

namespace App\Admin\Races\Controllers\Api;

use App\Admin\Races\Requests\RaceImportRequest;
use App\Admin\Races\Services\RaceExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RaceImportController extends Controller
{
    /**
     * @param  RaceExcelService  $raceExcelService  Race Excel export/import service.
     */
    public function __construct(
        private readonly RaceExcelService $raceExcelService,
    ) {}

    /**
     * Import validated Races and return the success response.
     *
     * @param  RaceImportRequest  $request  Validated Race import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(RaceImportRequest $request): JsonResponse
    {
        $this->raceExcelService->import($request->file('races_import'));

        return response()->json([
            'message' => 'Races imported successfully.',
        ], 200);
    }
}
