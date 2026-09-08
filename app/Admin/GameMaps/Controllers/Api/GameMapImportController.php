<?php

namespace App\Admin\GameMaps\Controllers\Api;

use App\Admin\GameMaps\Requests\GameMapImportRequest;
use App\Admin\GameMaps\Services\GameMapExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GameMapImportController extends Controller
{
    public function __construct(
        private readonly GameMapExcelService $gameMapExcelService,
    ) {}

    /**
     * Import validated Game Map settings and return the success response.
     */
    public function __invoke(GameMapImportRequest $request): JsonResponse
    {
        $this->gameMapExcelService->import($request->file('game_maps_import'));

        return response()->json([
            'message' => 'Game Maps imported successfully.',
        ], 200);
    }
}
