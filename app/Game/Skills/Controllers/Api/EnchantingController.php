<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Jobs\ProcessEnchant;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\EnchantingService;

class EnchantingController extends Controller {

    /**
     * @var EnchantingService $enchantingService
     */
    private $enchantingService;

    /**
     * Constructor
     *
     * @param EnchantingService $enchantingService
     * @return void
     */
    public function __construct(EnchantingService $enchantingService) {
        $this->enchantingService = $enchantingService;
    }

    public function fetchAffixes(Character $character) {
        return response()->json($this->enchantingService->fetchAffixes($character), 200);
    }

    public function enchant(EnchantingValidation $request, Character $character) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $slot = $this->enchantingService->getSlotFromInventory($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'invalid input.'], 422);
        }

        if (!$this->enchantingService->doesCostMatchForEnchanting($request->affix_ids, $slot->item->id, $request->cost)) {

            event(new GlobalMessageEvent($character->name . ' Was caught cheating. The value of their enchant was off. The Creator is watching you closely.'));

            return response()->json(['message' => 'You cannot do that.'], 422);
        }

        $timeOut = $this->enchantingService->timeForEnchanting($slot->item);

        event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

        ProcessEnchant::dispatch($character, $slot, $request->all());

        return response()->json([], 200);
    }
}
