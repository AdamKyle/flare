<?php

namespace App\Admin\Locations\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationType;

class LocationDetailTransformer
{
    /**
     * Transform the supplied internal Location detail data into its Admin detail representation.
     *
     * @param  array{location: Location, quest_item_drop_count: int}  $detailData  Internal Location detail data.
     * @return array{id: int, game_map: array{id: int, name: string}, name: string, description: string|null, type: int|null, x: int, y: int, pin_css_class: string|null, is_port: bool, can_players_enter: bool, can_auto_battle: bool, required_quest_item: array{id: int, name: string}|null, quest_reward_item: array{id: int, name: string}|null, hours_to_drop: int|null, minutes_between_delve_fights: int|null, quest_item_drop_count: int, is_cave_of_memories: bool, manual_fighting_only: bool} Admin Location detail representation.
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
     *
     * @param  Item|null  $item  Related Item, when one is set.
     * @return array{id: int, name: string}|null Compact Item identity.
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
