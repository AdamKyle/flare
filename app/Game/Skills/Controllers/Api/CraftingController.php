<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;

class CraftingController extends Controller {

    use FactionLoyalty;

    /**
     * @var CraftingService $craftingService
     */
    private $craftingService;

    /**
     * Constructor
     *
     * @param CraftingService $craftingService
     * @return void
     */
    public function __construct(CraftingService $craftingService) {
        $this->craftingService = $craftingService;
    }

    public function fetchItemsToCraft(Request $request, Character $character) {
        $event = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();

        $showCraftingForEvent = false;

        if (!is_null($event)) {
            $gameMap = GameMap::where('only_during_event_type', $event->type)->first();
            $globalEvent = GlobalEventGoal::where('event_type', $event->type)->first();

            if (!is_null($gameMap) && !is_null($globalEvent)) {
                $showCraftingForEvent = $character->map->game_map_id === $gameMap->id &&
                    $globalEvent->total_crafts < $globalEvent->max_crafts;
            }
        }

        return response()->json([
            'items'                => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp'                   => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc'   => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => !is_null($event) && $showCraftingForEvent,
        ]);
    }

    public function craft(CraftingValidation $request, Character $character, CraftingService $craftingService) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $craftingService->craft($character, $request->all());

        $event = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();

        $showCraftingForEvent = false;

        if (!is_null($event)) {
            $gameMap = GameMap::where('only_during_event_type', $event->type)->first();
            $globalEvent = GlobalEventGoal::where('event_type', $event->type)->first();

            if (!is_null($gameMap) && !is_null($globalEvent)) {
                $showCraftingForEvent = $character->map->game_map_id === $gameMap->id &&
                    $globalEvent->total_crafts < $globalEvent->max_crafts;
            }
        }

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false),
            'xp'    => $this->craftingService->getCraftingXP($character, $request->type),
            'show_craft_for_event' => !is_null($event) && $showCraftingForEvent,
        ], 200);
    }
}
