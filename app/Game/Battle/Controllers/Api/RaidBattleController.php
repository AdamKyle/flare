<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\Character;
use App\Game\Battle\Request\AttackTypeRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Game\Battle\Services\RaidBattleService;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class RaidBattleController extends Controller {

    /**
     * @var RaidBattleService $raidBattleService
     */
    private RaidBattleService $raidBattleService;

    /**
     * @param RaidBattleService $raidBattleService
     */
    public function __construct(RaidBattleService $raidBattleService) {
        $this->raidBattleService = $raidBattleService;
    }

    /**
     * Fetches raid monster details.
     *
     * @param Character $character
     * @param Monster $monster
     * @return JsonResponse
     */
    public function fetchRaidMonster(Character $character, Monster $monster): JsonResponse {

        if ($monster->is_raid_boss) {
            $raidBoss = RaidBoss::where('raid_boss_id', $monster->id)->first();

            if (is_null($raidBoss)) {
                ServerMessageHandler::sendBasicMessage($character->user, 'There is an issue with raids right now. Please contact The Creator on Discord for more assistance. You can find discord if you hover over
                your profile icon and select/tap Discord.');

                return response()->json();
            }

            $result = $this->raidBattleService->setUpRaidBossBattle($character, $raidBoss);
            $status = $result['status'];

            unset($result['status']);


            return response()->json($result, $status);
        }

        $result = $this->raidBattleService->setUpRaidCritterMonster($character, $monster);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Fight the raid monster (or boss)
     *
     * @param AttackTypeRequest $attackTypeRequest
     * @param Character $character
     * @param Monster $monster
     * @return JsonResponse
     */
    public function fightMonster(AttackTypeRequest $attackTypeRequest, Character $character, Monster $monster): JsonResponse {

        if ($monster->is_raid_monster) {

            $result = $this->raidBattleService->fightRaidMonster($character, $monster->id, $attackTypeRequest->attack_type, false);
            $status = $result['status'];
    
            unset($result['status']);

            return response()->json($result, $status);
        }

        $raidBoss = RaidBoss::where('raid_boss_id', $monster->id)->first();

        if (is_null($raidBoss)) {
            return response()->json([
                'message' => 'No Raid Boss was found ...',
            ], 422);
        }

        $result = $this->raidBattleService->fightRaidMonster($character, $monster->id, $attackTypeRequest->attack_type, true);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
