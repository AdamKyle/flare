<?php

namespace App\Admin\Services;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Location;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class ItemsService {

    use ResponseBuilder;

    public function formInputs(): array {
        return [
            'types'            => [
                'weapon',
                'bow',
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
            'skillTypes' => SkillTypeValue::$namedValues,
            'effects' => [
                ItemEffectsValue::WALK_ON_WATER,
                ItemEffectsValue::WALK_ON_DEATH_WATER,
                ItemEffectsValue::WALK_ON_MAGMA,
                ItemEffectsValue::LABYRINTH,
                ItemEffectsValue::DUNGEON,
                ItemEffectsValue::SHADOWPLANE,
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
            ],
            'specialtyTypes' => [
                ItemSpecialtyType::HELL_FORGED,
                ItemSpecialtyType::PURGATORY_CHAINS,
                ItemSpecialtyType::PIRATE_LORD_LEATHER,
            ],
            'locations'  => Location::select('name', 'id')->get(),
            'skills'     => GameSkill::pluck('name')->toArray(),
            'classes'    => GameClass::pluck('name', 'id')->toArray(),
        ];
    }

    public function cleanRequestData(array $params): array {

        if ($params['type'] !== 'quest') {
            $params['effect'] = null;
        }

        if (!filter_var($params['can_use_on_other_items'], FILTER_VALIDATE_BOOLEAN)) {
            $params['can_use_on_other_items'] = false;
            $params['holy_level'] = null;
        }

        if (!filter_var($params['usable'], FILTER_VALIDATE_BOOLEAN)) {
            $params['usable']             = false;
            $params['lasts_for']          = null;
            $params['damages_kingdoms']   = false;
            $params['stat_increase']      = null;
            $params['affects_skill_type'] = null;
        }

        if (!filter_var($params['damages_kingdoms'], FILTER_VALIDATE_BOOLEAN)) {
            $params['damages_kingdoms'] = false;
            $params['kingdom_damage']   = null;
        }

        if (filter_var($params['damages_kingdoms'], FILTER_VALIDATE_BOOLEAN)) {
            $params['damages_kingdoms']   = true;
            $params['lasts_for']          = null;
            $params['stat_increase']      = null;
            $params['affects_skill_type'] = null;
        }

        if (!filter_var($params['stat_increase'], FILTER_VALIDATE_BOOLEAN)) {
            $params['stat_increase']    = false;
            $params['increase_stat_by'] = 0;
        }

        if (is_null($params['affects_skill_type'])) {
            $params['increase_skill_bonus_by']          = null;
            $params['increase_skill_training_bonus_by'] = null;
        }


        if (!filter_var($params['can_resurrect'], FILTER_VALIDATE_BOOLEAN)) {
            $params['can_resurrect']       = false;
            $params['resurrection_chance'] = null;
        }

        if (!filter_var($params['can_craft'], FILTER_VALIDATE_BOOLEAN)) {
            $params['can_craft']            = false;
            $params['crafting_type']        = null;
            $params['craft_only']           = false;
            $params['skill_level_required'] = null;
            $params['skill_level_trivial']  = null;
        }

        return $params;
    }

    public function deleteItem(Item $item) {
        $name = $item->name;

        InventorySlot::where('item_id', $item->id)->delete();

        SetSlot::where('item_id', $item->id)->delete();

        foreach ($item->children as $child) {
            InventorySlot::where('item_id', $child->id)->delete();

            SetSlot::where('item_id', $child->id)->get();

            $child->delete();
        }

        $item->delete();

        return $this->successResult(['message' => 'success', $name . ' was deleted successfully.']);
    }
}
