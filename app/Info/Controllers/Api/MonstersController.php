<?php

namespace App\Info\Controllers\Api;

use App\Flare\Models\Monster;
use App\Game\Monsters\Services\MonsterReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MonstersController extends Controller
{
    /**
     * @param  MonsterReadService  $monsterReadService  Shared factual Monster read service.
     */
    public function __construct(
        private readonly MonsterReadService $monsterReadService,
    ) {}

    /**
     * Return the public, read-only full factual detail representation for the given Monster.
     *
     * @param  Monster  $monster  Monster to transform.
     * @return JsonResponse Monster detail JSON response.
     */
    public function show(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterReadService->detail($monster), 200);
    }
}
