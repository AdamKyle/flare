<?php

namespace App\Admin\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\Item;
use App\Game\Core\Currency\Services\CurrencyLimit;
use InvalidArgumentException;

class GiveToPlayerService
{
    public function giveItem(Character $character, Item $item): void
    {
        $inventory = $character->inventory ?? Inventory::create([
            'character_id' => $character->id,
        ]);

        $inventory->slots()->create([
            'item_id' => $item->id,
            'equipped' => false,
        ]);
    }

    public function giveCurrency(Character $character, string $currency, int $amount): void
    {
        $max = $this->maxForCurrency($currency);

        $character->update([
            $currency => min($character->{$currency} + $amount, $max),
        ]);
    }

    public function giveGoldBars(Character $character, int $amount): void
    {
        foreach ($character->kingdoms as $kingdom) {
            $kingdom->update([
                'gold_bars' => min($kingdom->gold_bars + $amount, 1000),
            ]);
        }
    }

    private function maxForCurrency(string $currency): int
    {
        return match ($currency) {
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            default => throw new InvalidArgumentException('Invalid currency.'),
        };
    }
}
