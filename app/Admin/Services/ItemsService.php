<?php

namespace App\Admin\Services;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
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
                ItemEffectsValue::WALK_ON_WATER,
                ItemEffectsValue::WALK_ON_DEATH_WATER,
                ItemEffectsValue::WALK_ON_MAGMA,
                ItemEffectsValue::WALK_ON_ICE,
                ItemEffectsValue::LABYRINTH,
                ItemEffectsValue::DUNGEON,
                ItemEffectsValue::SHADOW_PLANE,
                ItemEffectsValue::HELL,
                ItemEffectsValue::TELEPORT_TO_CELESTIAL,
                ItemEffectsValue::AFFIXES_IRRESISTIBLE,
                ItemEffectsValue::CONTINUE_LEVELING,
                ItemEffectsValue::GOLD_DUST_RUSH,
                ItemEffectsValue::MASS_EMBEZZLE,
                ItemEffectsValue::QUEEN_OF_HEARTS,
                ItemEffectsValue::PURGATORY,
                ItemEffectsValue::FACTION_POINTS,
                ItemEffectsValue::GET_COPPER_COINS,
                ItemEffectsValue::ENTER_PURGATORY_HOUSE,
                ItemEffectsValue::HIDE_CHAT_LOCATION,
                ItemEffectsValue::SETTLE_IN_ICE_PLANE,
                ItemEffectsValue::MERCENARY_SLOT_BONUS,
                ItemEffectsValue::TWISTED_TREE_BRANCH,
                ItemEffectsValue::TWISTED_DUNGEONS,
                ItemEffectsValue::THE_OLD_CHURCH,
            ],
            'specialtyTypes' => [
                ItemSpecialtyType::HELL_FORGED,
                ItemSpecialtyType::PURGATORY_CHAINS,
                ItemSpecialtyType::PIRATE_LORD_LEATHER,
                ItemSpecialtyType::CORRUPTED_ICE,
                ItemSpecialtyType::TWISTED_EARTH,
                ItemSpecialtyType::DELUSIONAL_SILVER,
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
