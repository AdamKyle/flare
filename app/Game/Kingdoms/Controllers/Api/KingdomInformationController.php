<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Transformers\KingdomAttackLogsTransformer;
use App\Game\Kingdoms\Transformers\KingdomTableTransformer;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Transformers\OtherKingdomTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class KingdomInformationController extends Controller
{
    private Manager $manager;

    private KingdomTransformer $kingdomTransformer;

    private BasicKingdomTransformer $basicKingdomTransformer;

    private OtherKingdomTransformer $otherKingdomTransformer;

    private KingdomAttackLogsTransformer $kingdomAttackLogsTransformer;

    private KingdomTableTransformer $kingdomTableTransformer;

    private UpdateKingdom $updateKingdom;

    public function __construct(
        Manager $manager,
        KingdomTransformer $kingdomTransformer,
        KingdomAttackLogsTransformer $kingdomAttackLogsTransformer,
        BasicKingdomTransformer $basicKingdomTransformer,
        KingdomTableTransformer $kingdomTableTransformer,
        OtherKingdomTransformer $otherKingdomTransformer,
        UpdateKingdom $updateKingdom
    ) {
        $this->manager = $manager;
        $this->kingdomTransformer = $kingdomTransformer;
        $this->kingdomAttackLogsTransformer = $kingdomAttackLogsTransformer;
        $this->basicKingdomTransformer = $basicKingdomTransformer;
        $this->otherKingdomTransformer = $otherKingdomTransformer;
        $this->kingdomTableTransformer = $kingdomTableTransformer;
        $this->updateKingdom = $updateKingdom;
    }

    public function getCharacterInfoForKingdom(Kingdom $kingdom, Character $character): JsonResponse
    {
        $kingdom = Kingdom::where('id', $kingdom->id)->first();

        if (is_null($kingdom)) {
            return response()->json(['message' => 'Kingdom not found.'], 422);
        }

        $transformer = $this->basicKingdomTransformer->setCharacter($character);

        $kingdom = new Item($kingdom, $transformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    public function getKingdomsList(Character $character): JsonResponse
    {
        return response()->json([
            'kingdoms' => $this->manager->createData(
                new Collection($character->kingdoms()->orderByDesc('is_capital')->orderBy('game_map_id')->orderBy('id')->get(), $this->kingdomTableTransformer)
            )->toArray(),
            'logs' => $this->manager->createData(
                new Collection(KingdomLog::where('character_id', $character->id)->orderBy('id', 'desc')->get(), $this->kingdomAttackLogsTransformer)
            )->toArray(),
        ]);
    }

    public function fetchKingdomDetails(Character $character, Kingdom $kingdom)
    {
        if ($character->id !== $kingdom->character_id) {
            return response()->json([
                'message' => 'Not allowed to do that.',
            ]);
        }

        return response()->json([
            'kingdom' => $this->manager->createData(
                new Item($kingdom, $this->kingdomTransformer)
            )->toArray(),
        ]);
    }

    public function getLocationData(Character $character, Kingdom $kingdom): JsonResponse
    {
        return response()->json(
            $this->manager->createData(
                new Item($kingdom, $this->kingdomTransformer)
            )->toArray(),
        );
    }

    public function updateLog(Character $character, KingdomLog $kingdomLog): JsonResponse
    {
        if ($kingdomLog->character_id !== $character->id) {
            return response()->json(['message' => 'Not allowed'], 422);
        }

        $kingdomLog->update([
            'opened' => true,
        ]);

        $this->updateKingdom->updateKingdomLogs($character->refresh());

        return response()->json();
    }

    public function deleteLog(Character $character, KingdomLog $kingdomLog): JsonResponse
    {
        if ($kingdomLog->character_id !== $character->id) {
            return response()->json(['message' => 'Not allowed'], 422);
        }

        $kingdomLog->delete();

        $this->updateKingdom->updateKingdomLogs($character->refresh());

        return response()->json();
    }

    public function deleteAllLogs(Character $character): JsonResponse
    {
        KingdomLog::where('character_id', $character->id)->delete();

        $this->updateKingdom->updateKingdomLogs($character->refresh());

        return response()->json();
    }
}
