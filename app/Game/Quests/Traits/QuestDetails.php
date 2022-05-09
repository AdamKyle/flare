<?php

namespace App\Game\Quests\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Quest;
use App\Game\Core\Values\FactionLevel;

trait QuestDetails {

    protected function validateParentQuest($quest, array $completedQuestIds): bool {
        if ($this->doesQuestHaveParent($quest)) {
            if (!$this->isParentQuestComplete($quest, $completedQuestIds)) {
                return false;
            }
        }

        return true;
    }

    protected function doesQuestHaveParent(Quest $quest): bool {
        return !is_null($quest->parent);
    }

    protected function isParentQuestComplete(Quest $quest, array $completedQuestIds): bool {
        return in_array($quest->parent->id, $completedQuestIds);
    }

    protected function questRequiresItem(Quest $quest): bool {
        return !is_null($quest->item);
    }

    protected function questRequiresSecondaryItem(Quest $quest): bool {
        return !is_null($quest->secondaryItem);
    }

    protected function questRequiresPlaneAccess(Quest $quest): bool {
        return !is_null($quest->access_to_map_id);
    }

    protected function questHasCurrenciesRequirement(Quest $quest): bool {
        return $quest->gold_dust_cost > 0 || $quest->gold_cost > 0 || $quest->shard_cost > 0;
    }

    protected function questHasFactionRequirement(Quest $quest): bool {
        return !is_null($quest->faction_game_map_id);
    }

    protected function fetchRequiredItem(Quest $quest, Character $character): ?InventorySlot {
        return $character->inventory->slots->filter(function($slot) use ($quest) {
            return $slot->item_id === $quest->item_id;
        })->first();
    }

    protected function fetchSecondaryRequiredItem(Quest $quest, Character $character): ?InventorySlot {
        return $character->inventory->slots->filter(function($slot) use ($quest) {
            return $slot->item_id === $quest->secondary_required_item;
        })->first();
    }

    protected function hasPlaneAccess(Quest $quest, Character $character): bool {
        $itemNeeded = $quest->requiredPlane->map_required_item;

        $planeAccessItem = $character->inventory->slots->filter(function($slot) use($itemNeeded) {
            return $slot->item->effect === $itemNeeded->effect;
        })->first();

        if (is_null($planeAccessItem)) {
            return false;
        }

        return true;
    }

    protected function hasMetFactionRequirement(Character $character, Quest $quest): bool {
        $faction = $character->factions->where('game_map_id', $quest->faction_game_map_id)->first();

        if ($quest->required_faction_level > 4) {
            if (!FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {
                return false;
            }
        } else {
            if ($faction->current_level < $quest->required_faction_level) {
                return false;
            }
        }

        return true;
    }

    protected function canPay(Character $character, Quest $quest) : bool {
        $canPay = true;

        if ($quest->gold_cost > 0) {
            $canPay = $character->gold >= $quest->gold_cost;
        }

        if ($quest->gold_dust_cost > 0) {
            $canPay = $character->gold_dust >= $quest->gold_dust_cost;
        }

        if ($quest->shard_cost > 0) {
            $canPay = $character->shards >= $quest->shard_cost;
        }

        if ($quest->copper_coin_cost > 0) {
            $canPay = $character->copper_coins >= $quest->copper_coin_cost;
        }

        return $canPay;
    }
}
