<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\Location;
use App\Flare\Transformers\ItemTransformer;
use League\Fractal\TransformerAbstract;

class LocationTransformer extends TransformerAbstract {

    protected array $defaultIncludes = [
        'quest_reward_item',
        'required_quest_item',
    ];

    public function transform(Location $location): array {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'description' => $location->description,
            'can_players_enter' => $location->can_players_enter,
            'can_auto_battle' => $location->can_auto_battle,
            'location_type' => !is_null($location->location_type) ? $location->locationType()->getNamedValue() : null,
            'is_corrupted' => $location->is_corrupted,
            'enemy_strength_increase' => $location->enemy_strength_increase,
            'x' => $location->x,
            'y' => $location->y,
        ];
    }

    public function includeQuestRewardItem(Location $location) {
        $questRewardItem = $location->questRewardItem;

        if (is_null($questRewardItem)) {
            return null;
        }

        return $this->item($questRewardItem, ItemTransformer::class);
    }

    public function includeRequiredQuestItem(Location $location) {
        $questRewardItem = $location->requiredQuestItem;

        if (is_null($questRewardItem)) {
            return null;
        }

        return $this->item($questRewardItem, ItemTransformer::class);
    }
}