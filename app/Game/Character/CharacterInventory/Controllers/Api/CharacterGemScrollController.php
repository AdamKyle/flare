<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Game\Character\CharacterInventory\Services\CharacterGemScrollService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterGemScrollController extends Controller
{
    public function __construct(
        private readonly CharacterGemScrollService $characterGemScrollService,
    ) {}

    /**
     * Activate an owned Alchemy Bag Gem Scroll for the Character's current generated Gem World.
     */
    public function use(Character $character, AlchemyBagSlot $alchemyBagSlot): JsonResponse
    {
        $result = $this->characterGemScrollService->useScroll($character, $alchemyBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Extend an owned active Map Gem Scroll using an owned Alchemy Bag fill Scroll.
     */
    public function fillMapScroll(Character $character, CharacterGameMapGemScroll $characterGameMapGemScroll, AlchemyBagSlot $alchemyBagSlot): JsonResponse
    {
        $result = $this->characterGemScrollService->fillMapScroll($character, $characterGameMapGemScroll, $alchemyBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Extend an owned active Location Gem Scroll using an owned Alchemy Bag fill Scroll.
     */
    public function fillLocationScroll(Character $character, CharacterGameLocationGemScroll $characterGameLocationGemScroll, AlchemyBagSlot $alchemyBagSlot): JsonResponse
    {
        $result = $this->characterGemScrollService->fillLocationScroll($character, $characterGameLocationGemScroll, $alchemyBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Remove an owned active Map Gem Scroll.
     */
    public function removeMapScroll(Character $character, CharacterGameMapGemScroll $characterGameMapGemScroll): JsonResponse
    {
        $result = $this->characterGemScrollService->removeMapScroll($character, $characterGameMapGemScroll);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Remove an owned active Location Gem Scroll.
     */
    public function removeLocationScroll(Character $character, CharacterGameLocationGemScroll $characterGameLocationGemScroll): JsonResponse
    {
        $result = $this->characterGemScrollService->removeLocationScroll($character, $characterGameLocationGemScroll);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
