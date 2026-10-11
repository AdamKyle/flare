<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Game\ClassRanks\Transformers\ClassDetailTransformer;
use App\Game\Core\Transformers\RaceDetailTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GameDataDetailsController extends Controller
{
    /**
     * @param RaceDetailTransformer $raceDetailTransformer
     * @param ClassDetailTransformer $classDetailTransformer
     */
    public function __construct(
        private readonly RaceDetailTransformer $raceDetailTransformer,
        private readonly ClassDetailTransformer $classDetailTransformer,
    ) {}

    /**
     * Return the player-safe factual detail for a Race.
     *
     * @param GameRace $gameRace
     * @return JsonResponse
     */
    public function race(GameRace $gameRace): JsonResponse
    {
        return response()->json(
            $this->raceDetailTransformer->transform($gameRace),
            200
        );
    }

    /**
     * Return the player-safe factual detail for a Class.
     *
     * @param GameClass $gameClass
     * @return JsonResponse
     */
    public function gameClass(GameClass $gameClass): JsonResponse
    {
        return response()->json(
            $this->classDetailTransformer->transform($gameClass),
            200
        );
    }
}
