<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Request\RankedFightRequest;
use App\Game\Battle\Request\RankFightSetUpRequest;
use App\Game\Battle\Services\RankFightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Http\Controllers\Controller;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Flare\Handlers\CheatingCheck;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Models\User;
use App\Flare\Transformers\MonsterTransformer;

class RankFightController extends Controller {

    /**
     * @var RankFightService $rankFightService
     */
    private RankFightService $rankFightService;

    /**
     * @param RankFightService $rankFightService
     */
    public function __construct(RankFightService $rankFightService) {
        $this->rankFightService = $rankFightService;
    }

    /**
     * Setup rank fight.
     *
     * @param RankFightSetUpRequest $request
     * @param Character $character
     * @param Monster $monster
     * @return JsonResponse
     */
    public function setUpRankFight(RankFightSetUpRequest $request, Character $character, Monster $monster): JsonResponse {
        $result = $this->rankFightService->setupFight($character, $monster, $request->rank);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param RankedFightRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function fightRankedMonster(RankedFightRequest $request, Character $character): JsonResponse {

        $result = $this->rankFightService->fight($character, $request->attack_type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }


}
