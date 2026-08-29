<?php

namespace App\Admin\Npcs\Controllers\Api;

use App\Admin\Npcs\Requests\NpcImportRequest;
use App\Admin\Npcs\Services\NpcExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcImportController extends Controller
{
    /**
     * @param  NpcExcelService  $npcExcelService  NPC Excel export/import service.
     */
    public function __construct(
        private readonly NpcExcelService $npcExcelService,
    ) {}

    /**
     * Import validated NPCs and return the success response.
     *
     * @param  NpcImportRequest  $request  Validated NPC import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(NpcImportRequest $request): JsonResponse
    {
        $this->npcExcelService->import($request->file('npcs_import'));

        return response()->json([
            'message' => 'NPCs imported successfully.',
        ], 200);
    }
}
