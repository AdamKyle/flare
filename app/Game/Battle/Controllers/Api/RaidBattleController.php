<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\Character;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Game\Battle\Services\RaidBattleService;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class RaidBattleController extends Controller {

    private RaidBattleService $raidBattleService;

    public function __construct(RaidBattleService $raidBattleService) {
        $this->raidBattleService = $raidBattleService;
    }

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
}
