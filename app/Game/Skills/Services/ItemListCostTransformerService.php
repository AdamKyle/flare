<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ItemListCostTransformerService {

    public function reduceCostOfAlchemyItems(Character $character, Collection $items, bool $showMerchantMessage): SupportCollection {
        if ($character->classType()->isArcaneAlchemist()) {
            if ($showMerchantMessage) {
                event(new ServerMessageEvent($character->user, 'As a Arcane Alchemist you get 15% discount on creating alchemy items as well as a 15% Crafting Timeout Reduction. The discount has been applied to the items list.'));
            }

            return $this->reduceCostForAlchemy($items, 0.15);
        }

        if ($character->classType()->isMerchant()) {
            if ($showMerchantMessage) {
                event(new ServerMessageEvent($character->user, 'As a Merchant you get 10% discount on creating alchemy items. The discount has been applied to the items list.'));
            }

            return $this->reduceCostForAlchemy($items, 0.15);
        }

        return $items;
    }

    public function reduceCostOfCraftingItems(Character $character, Collection $items, bool $showMerchantMessage): SupportCollection {
        if ($character->classType()->isBlacksmith()) {
            if ($showMerchantMessage) {
                event(new ServerMessageEvent($character->user, 'As a Blacksmith, you get 25% reduction on crafting time out for weapons and armour, as well as cost reduction. Items in the list have been adjusted.'));
            }

            return $this->reduceCostForCrafting($items, 0.25);
        }

        if ($character->classType()->isMerchant()) {
            if ($showMerchantMessage) {
                event(new ServerMessageEvent($character->user, 'As a Merchant you get 30% discount on crafting items. The items in the list have been adjusted.'));
            }

            return $this->reduceCostForCrafting($items, 0.30);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            if ($showMerchantMessage) {
                event(new ServerMessageEvent($character->user, 'As a Arcane Alchemist, you get 15% reduction on crafting time out for Spells, as well as cost reduction. Items in the list have been adjusted.'));
            }

            return $this->reduceCostForCrafting($items, 0.15);
        }

        return $items;
    }

    private function reduceCostForAlchemy(Collection $items, float $reduction): SupportCollection {
        return $items->transform(function($item) use($reduction) {
            $goldDustCost = $item->gold_dust_cost;
            $shardsCost   = $item->shards_cost;

            $goldDustCost = $goldDustCost - $goldDustCost * $reduction;
            $shardsCost   = $shardsCost - $shardsCost * $reduction;

            $item->gold_dust_cost = $goldDustCost;
            $item->shards_cost    = $shardsCost;

            return $item;
        });
    }

    private function reduceCostForCrafting(Collection $items, float $reduction): SupportCollection {
        return $items->transform(function($item) use ($reduction) {
            $cost = $item->cost;

            $cost = floor($cost - $cost * $reduction);

            $item->cost = $cost;

            return $item;
        });
    }
}
