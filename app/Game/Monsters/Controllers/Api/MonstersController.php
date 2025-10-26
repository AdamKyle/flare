<?php

namespace App\Game\Monsters\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Services\MonsterStatsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Psr\SimpleCache\InvalidArgumentException;

class MonstersController extends Controller
{
    /**
     * @param MonsterListService $monsterListService
     * @param MonsterStatsService $monsterStatsService
     */
    public function __construct(private readonly MonsterListService $monsterListService, private readonly MonsterStatsService $monsterStatsService) {}

    /**
     * @param Character $character
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function listMonsters(Character $character): JsonResponse
    {
        $response = $this->monsterListService->getMonstersForCharacter($character);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Monster $monster
     * @param Character $character
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function getMonsterStats(Monster $monster, Character $character): JsonResponse
    {
        $response = $this->monsterStatsService->getMonsterStats($character, $monster);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
