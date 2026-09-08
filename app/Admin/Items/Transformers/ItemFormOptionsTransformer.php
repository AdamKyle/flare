<?php

namespace App\Admin\Items\Transformers;

use App\Flare\Models\GameClass;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemCraftingType;
use App\Game\Core\Items\Values\ItemDefaultPosition;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Skills\Values\SkillTypeValue;

class ItemFormOptionsTransformer
{
    /**
     * Transform the supplied internal Item form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'types' => array_map(
                fn (ItemCatalogType $type): string => $type->value,
                ItemCatalogType::cases(),
            ),
            'default_positions' => array_map(
                fn (ItemDefaultPosition $type): string => $type->value,
                ItemDefaultPosition::cases(),
            ),
            'crafting_types' => array_map(
                fn (ItemCraftingType $type): string => $type->value,
                ItemCraftingType::cases(),
            ),
            'alchemy_types' => array_map(
                fn (AlchemyItemType $type): string => $type->value,
                AlchemyItemType::cases(),
            ),
            'specialty_types' => array_map(
                fn (ItemSpecialtyType $type): string => $type->value,
                ItemSpecialtyType::cases(),
            ),
            'effects' => array_map(
                fn (ItemEffectType $type): string => $type->value,
                ItemEffectType::cases(),
            ),
            'skill_types' => array_map(
                fn (SkillTypeValue $type): int => $type->value,
                SkillTypeValue::cases(),
            ),
            'item_skills' => $formOptions['item_skills']->map(fn (ItemSkill $itemSkill): array => [
                'value' => $itemSkill->id,
                'label' => $itemSkill->name,
            ])->values()->all(),
            'locations' => $formOptions['locations']->map(fn (Location $location): array => [
                'value' => $location->id,
                'label' => $location->name,
            ])->values()->all(),
            'classes' => $formOptions['classes']->map(fn (GameClass $gameClass): array => [
                'value' => $gameClass->id,
                'label' => $gameClass->name,
            ])->values()->all(),
        ];
    }
}
