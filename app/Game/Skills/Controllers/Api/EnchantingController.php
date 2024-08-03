<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\EnchantingService;
use App\Http\Controllers\Controller;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class EnchantingController extends Controller
{
    /**
     * @var EnchantingService
     */
    private $enchantingService;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(EnchantingService $enchantingService)
    {
        $this->enchantingService = $enchantingService;
    }

    public function fetchAffixes(Character $character)
    {
        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character, true),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
        ]);
    }

    public function enchant(EnchantingValidation $request, Character $character)
    {
        if (! $character->can_craft) {
            return response()->json(['message' => 'Cannot Craft.'], 429);
        }

        $slot = $this->enchantingService->getSlotFromInventory($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Invalid Slot.'], 422);
        }

        if ($slot->item->type === 'quest') {
            return response()->json(['message' => 'Invalid Type.'], 422);
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $request->affix_ids, $slot->item->id);

        if ($cost > $character->gold || $cost === 0) {
            ServerMessageHandler::handleMessage($character->user, 'enchantment_failed', 'Not enough gold to enchant that.');

            return response()->json($this->enchantingService->fetchAffixes($character->refresh()));
        }

        $timeOut = $this->enchantingService->timeForEnchanting($slot->item);

        event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

        $this->enchantingService->enchant($character, $request->all(), $slot, $cost);

        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character->refresh(), true, false),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
        ]);
    }
}
