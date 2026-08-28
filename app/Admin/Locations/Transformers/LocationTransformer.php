<?php

namespace App\Admin\Locations\Transformers;

use App\Flare\Models\Location;

class LocationTransformer
{
    /**
     * Transform a Location into its Admin API representation.
     */
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'game_map_id' => $location->game_map_id,
            'name' => $location->name,
            'description' => $location->description,
            'quest_reward_item_id' => $location->quest_reward_item_id,
            'required_quest_item_id' => $location->required_quest_item_id,
            'is_port' => $location->is_port,
            'can_players_enter' => $location->can_players_enter,
            'can_auto_battle' => $location->can_auto_battle,
            'x' => $location->x,
            'y' => $location->y,
            'type' => $location->type,
            'pin_css_class' => $location->pin_css_class,
            'hours_to_drop' => $location->hours_to_drop,
            'minutes_between_delve_fights' => $location->minutes_between_delve_fights,
        ];
    }
}
