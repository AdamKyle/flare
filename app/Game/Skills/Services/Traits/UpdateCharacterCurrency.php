<?php

namespace App\Game\Skills\Services\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use Exception;

trait UpdateCharacterCurrency
{
    /**
     * Update the characters gold when crafting.
     *
     * Subtract cost from gold.
     *
     * @throws Exception
     */
    public function updateCharacterGold(Character $character, Item $item): void
    {

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.30);
        }

        if ($character->classType()->isBlacksmith() && (
            in_array($item->type, [ItemType::WEAPON->value, ItemType::STAVE->value, ItemType::HAMMER->value, ItemType::BOW->value, ItemType::GUN->value, ItemType::MACE->value, ItemType::FAN->value, ItemType::SCRATCH_AWL->value, ItemType::RING->value, ItemType::SWORD->value, ItemType::CENSOR->value, ItemType::CLAW->value, ItemType::WAND->value], true) || ArmourType::tryFrom($item->type) !== null
        )) {
            $cost = floor($cost - $cost * 0.25);
        }

        if ($character->classType()->isArcaneAlchemist() && in_array($item->type, [ItemType::SPELL_HEALING->value, ItemType::SPELL_DAMAGE->value], true)) {
            $cost = floor($cost - $cost * 0.15);
        }

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }

    /**
     * Update character copper coins and gold dust.
     *
     * @throws Exception
     */
    public function updateTrinketCost(Character $character, Item $item): void
    {

        $copperCoinCost = $item->copper_coin_cost;
        $goldDustCost = $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $copperCoinCost = floor($copperCoinCost - $copperCoinCost * 0.10);
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
        }

        $character->update([
            'copper_coins' => $character->copper_coins - $copperCoinCost,
            'gold_dust' => $character->gold_dust - $goldDustCost,
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }

    /**
     * Update the alchemy currencies
     *
     * @throws Exception
     */
    public function updateAlchemyCost(Character $character, Item $item): void
    {
        $goldDustCost = $item->gold_dust_cost;
        $shardsCost = $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost = floor($shardsCost - $shardsCost * 0.10);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.15);
            $shardsCost = floor($shardsCost - $shardsCost * 0.15);
        }

        $character->update([
            'gold_dust' => ($character->gold_dust - $goldDustCost),
            'shards' => ($character->shards - $shardsCost),
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }
}
