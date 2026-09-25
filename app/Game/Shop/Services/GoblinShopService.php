<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class GoblinShopService
{
    use ResponseBuilder;

    /**
     * @param Pagination $pagination
     * @param UsableItemTransformer $usableItemTransformer
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     */
    public function __construct(
        private readonly Pagination $pagination,
        private readonly UsableItemTransformer $usableItemTransformer,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
    ) {}

    /**
     * Paginate the Items the Goblin Shop sells for Gold Bars.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function fetchItemsForShop(Character $character, int $perPage = 10, int $page = 1): array
    {
        $items = Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('gold_bars_cost', '>', 0)
            ->orderBy('gold_bars_cost')
            ->get();

        return $this->pagination->buildPaginatedDate($items, $this->usableItemTransformer, $perPage, $page);
    }

    /**
     * Buy an amount of a Goblin Shop Item, paying the Gold Bars from the character's kingdoms.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return array
     */
    public function buyItem(Character $character, Item $item, int $amount): array
    {
        $purchaseBlocker = $this->resolvePurchaseBlocker($character, $item, $amount);

        if (! is_null($purchaseBlocker)) {
            return $this->errorResult($purchaseBlocker);
        }

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($character->kingdoms()->get(), $item->gold_bars_cost * $amount);

        $this->giveItems($character, $item, $amount);

        $character = $character->refresh();

        return $this->successResult([
            'message' => $this->purchasedMessage($item, $amount),
            'character_gold_bars' => $character->kingdoms->sum('gold_bars'),
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }

    /**
     * Can the character buy this alchemy item?
     *
     * @param Character $character
     * @return bool
     */
    public function canBuyAlchemyItem(Character $character): bool
    {
        return $character->canAddToAlchemyBag(1);
    }

    /**
     * Resolve why the character cannot buy the requested amount of the Item.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return string|null
     */
    private function resolvePurchaseBlocker(Character $character, Item $item, int $amount): ?string
    {
        if (is_null($item->gold_bars_cost) || $item->gold_bars_cost <= 0) {
            return 'The Goblin Shop does not sell this item.';
        }

        if (! HandleGoldBarsAsACurrency::hasTheGoldBars($character->kingdoms()->get(), $item->gold_bars_cost * $amount)) {
            return 'Not enough gold bars. Go slay monsters to stock your treasury.';
        }

        if ($item->type === ItemCatalogType::ALCHEMY->value && ! $character->canAddToAlchemyBag($amount)) {
            return 'Your alchemy bag cannot hold that many more items.';
        }

        if ($item->type !== ItemCatalogType::ALCHEMY->value && $character->getInventoryCount() + $amount > $character->inventory_max) {
            return 'Your inventory is full. Cannot buy that many items.';
        }

        return null;
    }

    /**
     * Add the purchased amount of the Item to the alchemy bag or the inventory.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return void
     */
    private function giveItems(Character $character, Item $item, int $amount): void
    {
        if ($item->type === ItemCatalogType::ALCHEMY->value) {
            $this->addToAlchemyBag($character, $item, $amount);

            return;
        }

        $character->inventory->slots()->createMany(array_fill(0, $amount, [
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]));
    }

    /**
     * Stack the purchased amount of an alchemy Item in the character's alchemy bag.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return void
     */
    private function addToAlchemyBag(Character $character, Item $item, int $amount): void
    {
        $alchemyBag = AlchemyBag::firstOrCreate(['character_id' => $character->id]);

        $existingSlot = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
            ->where('item_id', $item->id)
            ->first();

        if (! is_null($existingSlot)) {
            $existingSlot->update(['amount' => $existingSlot->amount + $amount]);

            return;
        }

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Describe a completed Goblin Shop purchase.
     *
     * @param Item $item
     * @param int $amount
     * @return string
     */
    private function purchasedMessage(Item $item, int $amount): string
    {
        if ($amount === 1) {
            return 'Purchased: '.$item->affix_name;
        }

        return 'Purchased: '.number_format($amount).' x '.$item->affix_name;
    }
}
