<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Flare\Models\MarketHistory;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Http\Request;

class MarketBoard
{
    private EquipItemService $equipItemService;

    public function __construct(EquipItemService $equipItemService)
    {
        $this->equipItemService = $equipItemService;
    }

    /**
     * Buy and replace item from market.
     */
    public function buyAndReplaceItem(Request $request, Character $character, MarketBoardModel $listing, int $price): void
    {
        $slot = $this->buyItem($character, $listing, $price, true);

        $request->merge([
            'slot_id' => $slot->id,
        ]);

        $this->equipItemService->setRequest($request)
            ->setCharacter($character)
            ->replaceItem();

        $this->updateCharacterAttackDataCache($character->refresh());

        $listing->delete();
    }

    /**
     * Buy item from market.
     */
    public function buyItem(Character $character, MarketBoardModel $listing, int $price, bool $replacing = false): InventorySlot
    {
        $character->update([
            'gold' => $character->gold - $price,
        ]);

        MarketHistory::create([
            'item_id' => $listing->item_id,
            'sold_for' => $listing->listed_price,
        ]);

        $listingCharacter = $listing->character;

        $this->giveGoldToSeller($listingCharacter, $listing);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $listing->item_id,
        ]);

        if (! $replacing) {
            $listing->delete();
        }

        return $slot;
    }

    /**
     * Give gold to the seller.
     */
    protected function giveGoldToSeller(Character $listingCharacter, MarketBoardModel $listing): void
    {
        $gold = ($listing->listed_price - ($listing->listed_price * 0.05));

        $newGold = $gold + $listingCharacter->gold;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $listingCharacter->update([
            'gold' => $newGold,
        ]);

        $message = 'Sold market listing: ' . $listing->item->affix_name . ' for: ' . number_format($gold) . ' After fees (5% tax). You now have: ' . number_format($listingCharacter->gold) . '';

        ServerMessageHandler::handleMessage($listingCharacter->user, CharacterMessageTypes::SOLD_ITEM_ON_MARKET, $message);

        event(new UpdateTopBarEvent($listingCharacter->refresh()));
    }

    /**
     * Update character attack data.
     */
    protected function updateCharacterAttackDataCache(Character $character): void
    {
        CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
    }
}
