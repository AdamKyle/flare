<?php

namespace App\Admin\Services;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use App\Flare\Models\SetSlot;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Values\SkillTypeValue;

class ItemsService
{
    use ResponseBuilder;

    public function formInputs(): array
    {
        return [
            'types' => [
                'weapon',
                'bow',
                'gun',
                'fan',
                'scratch-awl',
                'stave',
                'hammer',
                'body',
                'shield',
                'leggings',
                'feet',
                'sleeves',
                'helmet',
                'gloves',
                'ring',
                'spell-healing',
                'spell-damage',
                'artifact',
                'quest',
                'alchemy',
                'artifact',
                'claw',
                'sword',
                'censor',
                'wand',
            ],
            'defaultPositions' => [
                'body',
                'leggings',
                'feet',
                'sleeves',
                'helmet',
                'gloves',
            ],
            'craftingTypes' => [
                'weapon',
                'armour',
                'ring',
                'spell',
                'artifact',
                'alchemy',
            ],
            'skillTypes' => SkillTypeValue::getValues(),
            'effects' => [
                ItemEffectType::WALK_ON_WATER->value,
                ItemEffectType::WALK_ON_DEATH_WATER->value,
                ItemEffectType::WALK_ON_MAGMA->value,
                ItemEffectType::WALK_ON_ICE->value,
                ItemEffectType::LABYRINTH->value,
                ItemEffectType::DUNGEON->value,
                ItemEffectType::SHADOW_PLANE->value,
                ItemEffectType::HELL->value,
                ItemEffectType::TELEPORT_TO_CELESTIAL->value,
                ItemEffectType::AFFIXES_IRRESISTIBLE->value,
                ItemEffectType::CONTINUE_LEVELING->value,
                ItemEffectType::GOLD_DUST_RUSH->value,
                ItemEffectType::MASS_EMBEZZLE->value,
                ItemEffectType::QUEEN_OF_HEARTS->value,
                ItemEffectType::PURGATORY->value,
                ItemEffectType::FACTION_POINTS->value,
                ItemEffectType::GET_COPPER_COINS->value,
                ItemEffectType::ENTER_PURGATORY_HOUSE->value,
                ItemEffectType::HIDE_CHAT_LOCATION->value,
                ItemEffectType::SETTLE_IN_ICE_PLANE->value,
                ItemEffectType::MERCENARY_SLOT_BONUS->value,
                ItemEffectType::TWISTED_TREE_BRANCH->value,
                ItemEffectType::TWISTED_DUNGEONS->value,
                ItemEffectType::THE_OLD_CHURCH->value,
                ItemEffectType::DELVE->value,
                ItemEffectType::DELVE_PACK_CHOICE->value,
            ],
            'specialtyTypes' => [
                ItemSpecialtyType::HELL_FORGED->value,
                ItemSpecialtyType::PURGATORY_CHAINS->value,
                ItemSpecialtyType::PIRATE_LORD_LEATHER->value,
                ItemSpecialtyType::CORRUPTED_ICE->value,
                ItemSpecialtyType::TWISTED_EARTH->value,
                ItemSpecialtyType::DELUSIONAL_SILVER->value,
                ItemSpecialtyType::LABYRINTH_CLOTH->value,
            ],
            'itemSkills' => ItemSkill::whereNull('parent_id')->get(),
            'locations' => Location::select('name', 'id')->get(),
            'skills' => GameSkill::pluck('name')->toArray(),
            'classes' => GameClass::pluck('name', 'id')->toArray(),
        ];
    }

    public function cleanRequestData(array $params): array
    {
        $booleanKeys = [
            'can_craft',
            'market_sellable',
            'can_drop',
            'craft_only',
            'usable',
            'damages_kingdoms',
            'stat_increase',
            'can_resurrect',
            'can_use_on_other_items',
            'ignores_caps',
            'is_mythic',
            'is_cosmic',
            'can_stack',
            'gains_additional_level',
            'has_gems_socketed',
        ];

        foreach ($booleanKeys as $key) {
            if (array_key_exists($key, $params)) {
                $params[$key] = filter_var($params[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (($params['type'] ?? null) !== 'quest') {
            $params['effect'] = null;
        }

        if (! ($params['can_use_on_other_items'] ?? false)) {
            $params['can_use_on_other_items'] = false;
            $params['holy_level'] = null;
        }

        if (! ($params['usable'] ?? false)) {
            $params['usable'] = false;
            $params['lasts_for'] = null;
            $params['damages_kingdoms'] = false;
            $params['stat_increase'] = null;
            $params['affects_skill_type'] = null;
        }

        if (! ($params['damages_kingdoms'] ?? false)) {
            $params['damages_kingdoms'] = false;
            $params['kingdom_damage'] = null;
        }

        if (($params['damages_kingdoms'] ?? false)) {
            $params['damages_kingdoms'] = true;
            $params['lasts_for'] = null;
            $params['stat_increase'] = null;
            $params['affects_skill_type'] = null;
        }

        if (! ($params['stat_increase'] ?? false)) {
            $params['stat_increase'] = false;
            $params['increase_stat_by'] = 0;
        }

        if (is_null($params['affects_skill_type'] ?? null)) {
            $params['increase_skill_bonus_by'] = null;
            $params['increase_skill_training_bonus_by'] = null;
        }

        if (! ($params['can_resurrect'] ?? false)) {
            $params['can_resurrect'] = false;
            $params['resurrection_chance'] = null;
        }

        if (! ($params['can_craft'] ?? false)) {
            $params['can_craft'] = false;
            $params['crafting_type'] = null;
            $params['craft_only'] = false;
            $params['skill_level_required'] = null;
            $params['skill_level_trivial'] = null;
        }

        return $params;
    }

    public function deleteItem(Item $item)
    {
        $name = $item->name;

        InventorySlot::where('item_id', $item->id)->delete();
        SetSlot::where('item_id', $item->id)->delete();

        foreach ($item->children as $child) {
            InventorySlot::where('item_id', $child->id)->delete();
            SetSlot::where('item_id', $child->id)->delete();
            $child->delete();
        }

        $item->delete();

        return $this->successResult(['message' => 'success', $name.' was deleted successfully.']);
    }
}
