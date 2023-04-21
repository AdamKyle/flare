<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\KingdomLog;
use App\Flare\Transformers\KingdomAttackLogsTransformer;
use App\Game\Kingdoms\Service\UpdateKingdom;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Flare\Transformers\OtherKingdomTransformer;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;

class KingdomInformationController extends Controller{

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var KingdomTransformer $kingdomTransformer
     */
    private KingdomTransformer $kingdomTransformer;

    /**
     * @var BasicKingdomTransformer $basicKingdomTransformer
     */
    private BasicKingdomTransformer $basicKingdomTransformer;

    /**
     * @var OtherKingdomTransformer $otherKingdomTransformer
     */
    private OtherKingdomTransformer $otherKingdomTransformer;

    /**
     * @var KingdomAttackLogsTransformer
     */
    private KingdomAttackLogsTransformer $kingdomAttackLogsTransformer;

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @param KingdomAttackLogsTransformer $kingdomAttackLogsTransformer
     * @param BasicKingdomTransformer $basicKingdomTransformer
     * @param OtherKingdomTransformer $otherKingdomTransformer
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(Manager $manager,
                                KingdomTransformer $kingdomTransformer,
                                KingdomAttackLogsTransformer $kingdomAttackLogsTransformer,
                                BasicKingdomTransformer $basicKingdomTransformer,
                                OtherKingdomTransformer $otherKingdomTransformer,
                                UpdateKingdom $updateKingdom)
    {
        $this->manager                      = $manager;
        $this->kingdomTransformer           = $kingdomTransformer;
        $this->kingdomAttackLogsTransformer = $kingdomAttackLogsTransformer;
        $this->basicKingdomTransformer      = $basicKingdomTransformer;
        $this->otherKingdomTransformer      = $otherKingdomTransformer;
        $this->updateKingdom                = $updateKingdom;
    }

    /**
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function getCharacterInfoForKingdom(Kingdom $kingdom, Character $character): JsonResponse {
        $kingdom = Kingdom::where('id', $kingdom->id)->first();

        if (is_null($kingdom)) {
            return response()->json(['message' => 'Kingdom not found.'], 422);
        }

        $transformer = $this->basicKingdomTransformer->setCharacter($character);

        $kingdom = new Item($kingdom, $transformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function getKingdomsList(Character $character): JsonResponse {
        return response()->json([
            'kingdoms' => $this->manager->createData(
                new Collection($character->kingdoms, $this->kingdomTransformer)
            )->toArray(),
            'logs'    => $this->manager->createData(
                new Collection(KingdomLog::where('character_id', $character->id)->orderBy('id', 'desc')->get(), $this->kingdomAttackLogsTransformer)
            )->toArray(),
        ]);
    }

    /**
     * @param Character $character
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function getLocationData(Character $character, Kingdom $kingdom): JsonResponse {
        return response()->json(
            $this->manager->createData(
                new Item($kingdom, $this->kingdomTransformer)
            )->toArray(),
        );
    }

    /**
     * @param Character $character
     * @param KingdomLog $kingdomLog
     * @return JsonResponse
     */
    public function updateLog(Character $character, KingdomLog $kingdomLog): JsonResponse {
        if ($kingdomLog->character_id !== $character->id) {
            return response()->json(['message' => 'Not allowed'], 422);
        }

        $kingdomLog->update([
            'opened' => true,
        ]);

        $this->updateKingdom->updateKingdomLogs($character->refresh());

        return response()->json();
    }

    /**
     * @param Character $character
     * @param KingdomLog $kingdomLog
     * @return JsonResponse
     */
    public function deleteLog(Character $character, KingdomLog $kingdomLog): JsonResponse {
        if ($kingdomLog->character_id !== $character->id) {
            return response()->json(['message' => 'Not allowed'], 422);
        }

        $kingdomLog->delete();

        $this->updateKingdom->updateKingdomLogs($character->refresh());

        return response()->json();
    }
}
