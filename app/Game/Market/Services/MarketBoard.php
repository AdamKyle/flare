<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Flare\Models\MarketHistory;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
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
        $this->equipItemService->validateReplacementEligibility($character, $listing->item, $request->position);

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
     * List a Batch Crafting produced item on the Market on the character's behalf.
     *
     * The requested listing price is raised to the item's real minimum selling price when it
     * falls below it, since a batch run cannot pause to ask the character to correct the price
     * the way the manual listing form does.
     *
     * @param  Character  $character  The character listing the item.
     * @param  Item  $item  The item being listed.
     * @param  int  $listingPrice  The requested listing price.
     * @return void This method does not return a value.
     */
    public function listBatchCraftedItem(Character $character, Item $item, int $listingPrice): void
    {
        $minimumPrice = SellItemCalculator::fetchMinPrice($item);
        $listPrice = $minimumPrice !== 0 && $minimumPrice > $listingPrice ? $minimumPrice : $listingPrice;
        $listPrice = min($listPrice, CurrencyLimit::MAX_GOLD);

        MarketBoardModel::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'listed_price' => $listPrice,
        ]);

        $itemName = $item->affix_name ?? $item->name;

        ServerMessageHandler::sendBasicMessage($character->user, 'Listed: '.$itemName.' For: '.number_format($listPrice).' Gold.');
    }

    /**
     * Give gold to the seller.
     */
    private function giveGoldToSeller(Character $listingCharacter, MarketBoardModel $listing): void
    {
        $gold = ($listing->listed_price - ($listing->listed_price * 0.05));

        $newGold = $gold + $listingCharacter->gold;

        if ($newGold > CurrencyLimit::MAX_GOLD) {
            $newGold = CurrencyLimit::MAX_GOLD;
        }

        $listingCharacter->update([
            'gold' => $newGold,
        ]);

        $message = 'Sold market listing: '.$listing->item->affix_name.' for: '.number_format($gold).' After fees (5% tax). You now have: '.number_format($listingCharacter->gold).'';

        ServerMessageHandler::handleMessage($listingCharacter->user, CharacterMessageTypes::SOLD_ITEM_ON_MARKET, $message);

        event(new UpdateCharacterBaseDetailsEvent($listingCharacter->refresh()));
    }

    /**
     * Update character attack data.
     */
    private function updateCharacterAttackDataCache(Character $character): void
    {
        CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
    }
}
