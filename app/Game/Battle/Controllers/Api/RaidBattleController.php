<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Request\AttackTypeRequest;
use App\Game\Battle\Services\Concerns\HandleCachedRaidCritterHealth;
use App\Game\Battle\Services\RaidBattleService;
use App\Http\Controllers\Controller;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Http\JsonResponse;

class RaidBattleController extends Controller
{
    use HandleCachedRaidCritterHealth;

    private RaidBattleService $raidBattleService;

    public function __construct(RaidBattleService $raidBattleService)
    {
        $this->raidBattleService = $raidBattleService;
    }

    /**
     * Fetches raid monster details.
     */
    public function fetchRaidMonster(Character $character, Monster $monster): JsonResponse
    {

        $this->deleteMonsterCacheHealth($character->id, $monster->id);

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
     */
    public function fightMonster(AttackTypeRequest $attackTypeRequest, Character $character, Monster $monster): JsonResponse
    {

        if ($monster->is_raid_monster) {
            $result = $this->raidBattleService->fightRaidMonster($character, $monster->id, $attackTypeRequest->attack_type);

            $status = $result['status'];

            unset($result['status']);

            if ($result['monster_current_health'] <= 0) {
                event(new AttackTimeOutEvent($character));
            }

            return response()->json($result, $status);
        }

        $raidBossParticipation = RaidBossParticipation::where('character_id', $character->id)->first();

        if (! is_null($raidBossParticipation)) {
            if ($raidBossParticipation->attacks_left <= 0) {
                return response()->json([
                    'message' => 'Error! You cannot attack until tomorrow. Out of attacks!',
                ], 422);
            }
        }

        $raidBoss = RaidBoss::where('raid_boss_id', $monster->id)->first();

        if (is_null($raidBoss)) {
            return response()->json([
                'message' => 'No Raid Boss was found ...',
            ], 422);
        }

        $result = $this->raidBattleService->setRaidBossHealth($raidBoss->boss_current_hp)->fightRaidMonster($character, $monster->id, $attackTypeRequest->attack_type, true);
        $status = $result['status'];

        unset($result['status']);

        if ($result['monster_current_health'] <= 0) {
            event(new AttackTimeOutEvent($character));
        }

        return response()->json($result, $status);
    }
}
