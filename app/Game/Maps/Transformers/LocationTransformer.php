<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use League\Fractal\TransformerAbstract;

class LocationTransformer extends TransformerAbstract
{
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'game_map_id' => $location->game_map_id,
            'quest_reward_item_id' => $location->quest_reward_item_id,
            'required_quest_item_id' => $location->required_quest_item_id,
            'required_quest_item_name' => $this->getRequiredItemName($location),
            'description' => $location->description,
            'is_port' => $location->is_port,
            'can_players_enter' => $location->can_players_enter,
            'increases_enemy_stats_by' => is_null($location->enemy_strength_type) ? null : LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type),
            'increase_enemy_percentage_by' => is_null($location->enemy_strength_type) ? null :  LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type),
            'can_auto_battle' => $location->can_auto_battle,
            'x' => $location->x,
            'y' => $location->y,
            'type' => $location->type,
            'type_name' => $this->getLocationTypeName($location),
            'raid_id' => $location->raid_id,
            'has_raid_boss' => $location->has_raid_boss,
            'is_corrupted' => $location->is_corrupted,
            'pin_css_class' => $location->pin_css_class,
            'hours_to_drop' => $location->hours_to_drop,
            'minutes_between_delve_fights' => $location->minutes_between_delve_fights,
            'delve_enemy_strength_increase' => $location->delve_enemy_strength_increase,
        ];
    }

    private function getLocationTypeName(Location $location): ?string {

        if (is_null($location->type)) {
            return null;
        }

        $locationType = new LocationType($location->type);

        if ($locationType->isPurgatorySmithHouse()) {
            return 'Purgatory Smiths House';
        }

        if ($locationType->isUnderWaterCaves()) {
            return 'Underwater Caves';
        }

        if ($locationType->isAlchemyChurch()) {
            return 'Alchemy Church';
        }

        if ($locationType->isGoldMines()) {
            return 'Gold Mines';
        }

        if ($locationType->isPurgatoryDungeons()) {
            return 'Purgatory Dungeons';
        }

        if ($locationType->isTheOldChurch()) {
            return 'The Old Church';
        }

        return null;
    }

    private function getRequiredItemName(Location $location): ?string {
        if (is_null($location->required_quest_item_id)) {
            return null;
        }

        return $location->requiredQuestItem->name;
    }
}