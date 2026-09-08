<?php

namespace App\Admin\Npcs\Controllers\Api;

use App\Admin\Npcs\Requests\NpcImportRequest;
use App\Admin\Npcs\Services\NpcExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcImportController extends Controller
{
    public function __construct(
        private readonly NpcExcelService $npcExcelService,
    ) {}

    /**
     * Import validated NPCs and return the success response.
     */
    public function __invoke(NpcImportRequest $request): JsonResponse
    {
        $this->npcExcelService->import($request->file('npcs_import'));

        return response()->json([
            'message' => 'NPCs imported successfully.',
        ], 200);
    }
}
