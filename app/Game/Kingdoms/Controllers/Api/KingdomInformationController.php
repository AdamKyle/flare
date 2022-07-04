<?php

namespace App\Game\Kingdoms\Controllers\Api;

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
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @param BasicKingdomTransformer $basicKingdomTransformer
     * @param OtherKingdomTransformer $otherKingdomTransformer
     */
    public function __construct(Manager                 $manager,
                                KingdomTransformer      $kingdomTransformer,
                                BasicKingdomTransformer $basicKingdomTransformer,
                                OtherKingdomTransformer $otherKingdomTransformer)
    {
        $this->manager                 = $manager;
        $this->kingdomTransformer      = $kingdomTransformer;
        $this->basicKingdomTransformer = $basicKingdomTransformer;
        $this->otherKingdomTransformer = $otherKingdomTransformer;
    }

    /**
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function getCharacterInfoForKingdom(Kingdom $kingdom, Character $character): JsonResponse {
        $kingdom = Kingdom::where('character_id', $character->id)->where('id', $kingdom->id)->first();

        if (is_null($kingdom)) {
            return response()->json(['message' => 'Kingdom not found.'], 422);
        }

        $kingdom = new Item($kingdom, $this->basicKingdomTransformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    /**
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function getOtherKingdomInfo(Kingdom $kingdom): JsonResponse {
        $kingdom = new Item($kingdom, $this->otherKingdomTransformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function getKingdomsList(Character $character): JsonResponse {
        return response()->json(
            $this->manager->createData(
                new Collection($character->kingdoms, $this->kingdomTransformer)
            )->toArray()
        );
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
}
