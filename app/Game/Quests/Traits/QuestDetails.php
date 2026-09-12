<?php

namespace App\Game\Quests\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Quest;
use App\Game\Core\Values\FactionLevel;

trait QuestDetails
{
    /**
     * Validate the Quest parent requirement for the character.
     *
     * @param mixed $quest
     */
    protected function validateParentQuest($quest, array $completedQuestIds): bool
    {
        if ($this->doesQuestHaveParent($quest)) {
            if (! $this->isParentQuestComplete($quest, $completedQuestIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the Quest has a parent Quest.
     */
    protected function doesQuestHaveParent(Quest $quest): bool
    {
        return ! is_null($quest->parent);
    }

    /**
     * Determine whether the character completed the parent Quest.
     */
    protected function isParentQuestComplete(Quest $quest, array $completedQuestIds): bool
    {
        return in_array($quest->parent->id, $completedQuestIds);
    }

    /**
     * Determine whether the Quest requires a primary Item.
     */
    protected function questRequiresItem(Quest $quest): bool
    {
        return ! is_null($quest->item);
    }

    /**
     * Determine whether the Quest requires a secondary Item.
     */
    protected function questRequiresSecondaryItem(Quest $quest): bool
    {
        return ! is_null($quest->secondaryItem);
    }

    /**
     * Determine whether the Quest requires access to another Map.
     */
    protected function questRequiresPlaneAccess(Quest $quest): bool
    {
        return ! is_null($quest->access_to_map_id);
    }

    /**
     * Determine whether the Quest has currency requirements.
     */
    protected function questHasCurrenciesRequirement(Quest $quest): bool
    {
        return $quest->gold_dust_cost > 0
            || $quest->gold_cost > 0
            || $quest->shard_cost > 0
            || $quest->copper_coin_cost > 0;
    }

    /**
     * Determine whether the Quest has a Faction requirement.
     */
    protected function questHasFactionRequirement(Quest $quest): bool
    {
        return ! is_null($quest->faction_game_map_id);
    }

    /**
     * Determine whether the Quest has a Faction Loyalty requirement.
     */
    protected function questHasFactionLoyaltyRequirement(Quest $quest): bool
    {
        return ! is_null($quest->assisting_npc_id) && ! is_null($quest->required_fame_level);
    }

    /**
     * Determine whether the character meets the Faction Loyalty requirements.
     */
    protected function hasMetFactionLoyaltyRequirements(Quest $quest, Character $character): bool
    {
        $factionLoyalty = $character->factionLoyalties()
            ->with('factionLoyaltyNpcs')
            ->whereHas('factionLoyaltyNpcs', function ($query) use ($quest) {
                $query->where('npc_id', $quest->assisting_npc_id);
            })
            ->first();

        if (is_null($factionLoyalty)) {
            return false;
        }

        $assistingNpc = $factionLoyalty->factionLoyaltyNpcs->filter(function ($factionLoyaltyNpc) use ($quest) {
            return $factionLoyaltyNpc->npc_id === $quest->assisting_npc_id;
        })->first();

        if (is_null($assistingNpc)) {
            return false;
        }

        return $assistingNpc->current_level >= $quest->required_fame_level;
    }

    /**
     * Fetch the Quest primary required Item.
     */
    protected function fetchRequiredItem(Quest $quest, Character $character): ?InventorySlot
    {
        return $character->inventory->slots->filter(function ($slot) use ($quest) {
            return $slot->item_id === $quest->item_id;
        })->first();
    }

    /**
     * Fetch the Quest secondary required Item.
     */
    protected function fetchSecondaryRequiredItem(Quest $quest, Character $character): ?InventorySlot
    {
        return $character->inventory->slots->filter(function ($slot) use ($quest) {
            return $slot->item_id === $quest->secondary_required_item;
        })->first();
    }

    /**
     * Determine whether the character has access to the required Map.
     */
    protected function hasPlaneAccess(Quest $quest, Character $character): bool
    {
        $itemNeeded = $quest->requiredPlane->requiredItem();

        $planeAccessItem = $character->inventory->slots->filter(function ($slot) use ($itemNeeded) {
            return $slot->item->effect === $itemNeeded->effect;
        })->first();

        if (is_null($planeAccessItem)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the character meets the Faction requirement.
     */
    protected function hasMetFactionRequirement(Character $character, Quest $quest): bool
    {
        $faction = $character->factions->where('game_map_id', $quest->faction_game_map_id)->first();

        if ($quest->required_faction_level > 4) {
            if (! FactionLevel::isMaxLevel($faction->current_level)) {
                return false;
            }
        } else {
            if ($faction->current_level < $quest->required_faction_level) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the character can pay the Quest currency requirements.
     */
    protected function canPay(Character $character, Quest $quest): bool
    {
        $hasGold = $character->gold >= $quest->gold_cost;
        $hasGoldDust = $character->gold_dust >= $quest->gold_dust_cost;
        $hasShards = $character->shards >= $quest->shard_cost;
        $copperCoins = $character->copper_coins >= $quest->copper_coin_cost;

        return $hasGold && $hasGoldDust && $hasShards && $copperCoins;
    }

    /**
     * Determine whether the character completed the required Quest.
     */
    protected function hasCompletedRequiredQuest(Character $character, Quest $quest): bool
    {
        if (! is_null($quest->required_quest_id)) {
            return $character->questsCompleted->where('quest_id', $quest->required_quest_id)->count() > 0;
        }

        return true;
    }

    /**
     * Determine whether the character completed the required Quest chain.
     */
    protected function hasCompletedRequiredQuestChain(Character $character, Quest $quest): bool
    {
        if (! is_null($quest->required_quest_chain)) {
            return $character->questsCompleted->whereIn('quest_id', $quest->required_quest_chain)->count() === count($quest->required_quest_chain);
        }

        return true;
    }
}
