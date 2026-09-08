<?php

namespace App\Admin\Monsters\Transformers;

use App\Admin\Monsters\Values\MonsterListCategory;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Game\Core\Values\CoreStatType;
use App\Game\Raids\Values\RaidAttackType;

class MonsterFormOptionsTransformer
{
    /**
     * Transform the supplied internal Monster form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'game_maps' => $formOptions['game_maps']->map(fn (GameMap $gameMap): array => [
                'value' => $gameMap->id,
                'label' => $gameMap->name,
            ])->values()->all(),
            'quest_items' => $formOptions['quest_items']->map(fn (Item $item): array => [
                'value' => $item->id,
                'label' => $item->name,
            ])->values()->all(),
            'damage_stats' => array_map(fn (CoreStatType $type): string => $type->value, CoreStatType::cases()),
            'location_types' => MonsterListCategory::allCategoryLocationTypes(),
            'raid_special_attack_types' => array_map(fn (RaidAttackType $type): int => $type->value, RaidAttackType::cases()),
        ];
    }
}
