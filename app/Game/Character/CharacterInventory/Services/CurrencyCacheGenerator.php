<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Character\CharacterInventory\Contracts\CurrencyCacheIssuer;
use App\Game\Core\Currency\Values\CurrencyCacheType;

class CurrencyCacheGenerator implements CurrencyCacheIssuer
{
    /**
     * Issue enough Compensation Caches to hold the requested amount and return the number created.
     *
     * @param int $characterId
     * @param CurrencyCacheType $type
     * @param int $totalAmount
     * @return int
     */
    public function issue(int $characterId, CurrencyCacheType $type, int $totalAmount): int
    {
        if ($totalAmount <= 0) {
            return 0;
        }

        $character = Character::find($characterId);

        if (is_null($character)) {
            return 0;
        }

        $this->give($character, $type, $totalAmount);

        return intdiv($totalAmount + $type->maxCacheAmount() - 1, $type->maxCacheAmount());
    }

    /**
     * Give the Character enough unique Compensation Caches in their Alchemy Bag to hold the total amount.
     *
     * @param Character $character
     * @param CurrencyCacheType $type
     * @param int $totalAmount
     * @return void
     */
    public function give(Character $character, CurrencyCacheType $type, int $totalAmount): void
    {
        if ($totalAmount <= 0) {
            return;
        }

        $alchemyBag = AlchemyBag::firstOrCreate(['character_id' => $character->id]);
        $remainingAmount = $totalAmount;

        while ($remainingAmount > 0) {
            $cacheAmount = min($remainingAmount, $type->maxCacheAmount());

            AlchemyBagSlot::create([
                'alchemy_bag_id' => $alchemyBag->id,
                'character_id' => $character->id,
                'item_id' => $this->createCacheItem($type, $cacheAmount)->id,
                'amount' => 1,
            ]);

            $remainingAmount -= $cacheAmount;
        }
    }

    /**
     * Persist one unique Compensation Cache Item holding the given amount of currency.
     *
     * @param CurrencyCacheType $type
     * @param int $cacheAmount
     * @return Item
     */
    private function createCacheItem(CurrencyCacheType $type, int $cacheAmount): Item
    {
        return Item::create([
            'name' => $type->label().' Compensation Cache',
            'description' => 'Rebuild compensation. Use this cache to withdraw its stored '.$type->label().'. It can be used until its stored currency is exhausted.',
            'type' => 'alchemy',
            'usable' => true,
            'can_stack' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_craft' => false,
            'craft_only' => false,
            'randomly_generated' => true,
            'currency_cache_type' => $type,
            'cache_amount' => $cacheAmount,
        ]);
    }
}
