<?php

namespace App\Admin\Monsters\Controllers\Api;

use App\Admin\Monsters\Requests\MonsterImportRequest;
use App\Admin\Monsters\Services\MonsterExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MonsterImportController extends Controller
{
    /**
     * @param  MonsterExcelService  $monsterExcelService  Monster Excel export/import service.
     */
    public function __construct(
        private readonly MonsterExcelService $monsterExcelService,
    ) {}

    /**
     * Import validated Monsters and return the resulting success or failure response.
     *
     * @param  MonsterImportRequest  $request  Validated Monster import request.
     * @return JsonResponse Import result JSON response.
     */
    public function __invoke(MonsterImportRequest $request): JsonResponse
    {
        $result = $this->monsterExcelService->import($request->file('monsters_import'));

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
