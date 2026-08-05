<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Requests\EnchantingAffixesRequest;
use App\Game\Skills\Requests\EnchantingItemsRequest;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Http\Controllers\Controller;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Http\JsonResponse;

class EnchantingController extends Controller
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        private EnchantingService $enchantingService,
        private CraftingService $craftingService,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
    ) {}

    public function fetchAffixes(Character $character): JsonResponse
    {
        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character, true),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    public function items(EnchantingItemsRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->enchantingService->fetchPaginatedItems($character, $request->source, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function affixes(EnchantingAffixesRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->enchantingService->fetchPaginatedAffixes($character, $request->type, $request->per_page, $request->page, $request->search_text)
        );
    }

    /**
     * @throws Exception
     */
    public function enchant(EnchantingValidation $request, Character $character): JsonResponse
    {
        if (! $character->can_craft) {
            return response()->json(['message' => 'You must wait to enchant again.'], 422);
        }

        $slot = $this->enchantingService->getSlotFromInventory($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Invalid Slot.'], 422);
        }

        if ($slot->item->type === 'quest') {
            return response()->json(['message' => 'You cannot enchant quest items.'], 422);
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $request->affix_ids, $slot->item->id);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::ENCHANTMENT_FAILED, 'Not enough gold to enchant that.');

            return response()->json([
                'affixes' => $this->enchantingService->fetchAffixes($character->refresh(), true, false),
                'skill_xp' => $this->enchantingService->getEnchantingXP($character),
                'enchant_succeeded' => false,
                'result_preview' => null,
            ]);
        }

        $timeOut = $this->enchantingService->timeForEnchanting($slot->item);

        event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

        $enchantSucceeded = $this->enchantingService->enchant($character, $request->all(), $slot, $cost);

        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character->refresh(), true, false),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
            'enchant_succeeded' => $enchantSucceeded,
            'result_preview' => $this->buildResultPreview($enchantSucceeded, $slot),
        ]);
    }

    private function buildResultPreview(bool $enchantSucceeded, InventorySlot|GlobalEventCraftingInventorySlot $slot): ?array
    {
        if (! $enchantSucceeded) {
            return null;
        }

        $refreshedSlot = $slot->fresh(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks']);

        if (is_null($refreshedSlot) || is_null($refreshedSlot->item)) {
            return null;
        }

        return $this->craftingItemPreviewTransformer->transform($refreshedSlot->item, $refreshedSlot->id);
    }
}
