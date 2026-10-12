<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\GuideQuest;

class WorldGuideQuestRequirementService
{
    /**
     * Return completed world and Quest requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        return array_values(array_filter([
            $this->factionLevel($character, $quest),
            $this->gameMapAccess($character, $quest),
            $this->quest($character, $quest),
            $this->questItem($character, $quest, true),
            $this->questItem($character, $quest, false),
            $this->currentMap($character, $quest),
        ]));
    }

    /**
     * Return the completed Faction level requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function factionLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_faction_id)) {
            return null;
        }

        $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

        return ! is_null($faction) && $faction->current_level >= $quest->required_faction_level
            ? 'required_faction_level'
            : null;
    }

    /**
     * Return the completed Game Map access requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function gameMapAccess(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_game_map_id)) {
            return null;
        }

        $gameMap = GameMap::find($quest->required_game_map_id);

        if (is_null($gameMap)) {
            return null;
        }

        $requiredItemId = $gameMap->requiredItem()?->id;
        $hasItem = $character->inventory->slots->contains(
            fn ($slot): bool => $slot->item->type === 'quest' && $slot->item->id === $requiredItemId,
        );

        return $hasItem ? 'required_game_map_id' : null;
    }

    /**
     * Return the completed Quest requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function quest(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_quest_id)) {
            return null;
        }

        return $character->questsCompleted()->where('quest_id', $quest->required_quest_id)->exists()
            ? 'required_quest_id'
            : null;
    }

    /**
     * Return the completed primary or secondary Quest Item requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param bool $primary
     * @return string|null
     */
    private function questItem(Character $character, GuideQuest $quest, bool $primary): ?string
    {
        $attribute = $primary ? 'required_quest_item_id' : 'secondary_quest_item_id';
        $itemId = $quest->{$attribute};

        if (is_null($itemId)) {
            return null;
        }

        $hasItem = $character->inventory->slots->contains(
            fn ($slot): bool => $slot->item->type === 'quest' && $slot->item->id === $itemId,
        );

        return $hasItem || $this->wasItemUsedInQuest($character, $itemId) ? $attribute : null;
    }

    /**
     * Return the completed current Game Map requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function currentMap(Character $character, GuideQuest $quest): ?string
    {
        return $character->map->game_map_id === $quest->be_on_game_map
            ? 'required_to_be_on_game_map_name'
            : null;
    }

    /**
     * Determine whether a completed Quest consumed the required Item.
     *
     * @param Character $character
     * @param int $itemId
     * @return bool
     */
    private function wasItemUsedInQuest(Character $character, int $itemId): bool
    {
        return $character->questsCompleted()
            ->whereHas('quest', function ($query) use ($itemId) {
                $query->where(function ($itemQuery) use ($itemId) {
                    $itemQuery->where('item_id', $itemId)->orWhere('secondary_required_item', $itemId);
                });
            })
            ->exists();
    }
}
