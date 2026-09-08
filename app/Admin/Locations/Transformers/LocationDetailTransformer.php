<?php

namespace App\Admin\Locations\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationType;

class LocationDetailTransformer
{
    /**
     * Transform the supplied internal Location detail data into its Admin detail representation.
     */
    public function transform(array $detailData): array
    {
        /** @var Location $location */
        $location = $detailData['location'];

        $locationType = is_null($location->type) ? null : LocationType::from($location->type);

        return [
            'id' => $location->id,
            'game_map' => [
                'id' => $location->game_map_id,
                'name' => $location->map?->name ?? '',
            ],
            'name' => $location->name,
            'description' => $location->description,
            'type' => $location->type,
            'x' => $location->x,
            'y' => $location->y,
            'pin_css_class' => $location->pin_css_class,
            'is_port' => $location->is_port,
            'can_players_enter' => $location->can_players_enter,
            'can_auto_battle' => $location->can_auto_battle,
            'required_quest_item' => $this->transformRelatedItem($location->requiredQuestItem),
            'quest_reward_item' => $this->transformRelatedItem($location->questRewardItem),
            'hours_to_drop' => $location->hours_to_drop,
            'minutes_between_delve_fights' => $location->minutes_between_delve_fights,
            'quest_item_drop_count' => $detailData['quest_item_drop_count'],
            'is_cave_of_memories' => $locationType?->isCaveOfMemories() ?? false,
            'manual_fighting_only' => $locationType?->canDropManualQuestItems() ?? false,
        ];
    }

    /**
     * Transform a related quest Item into its compact identity representation.
     */
    private function transformRelatedItem(?Item $item): ?array
    {
        if (is_null($item)) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
        ];
    }
}
