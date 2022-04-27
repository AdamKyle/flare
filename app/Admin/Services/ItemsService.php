<?php

namespace App\Admin\Services;

use App\Flare\Models\Location;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class ItemsService {

    use ResponseBuilder;

    public function formInputs(): array {
        return [
            'types'            => [
                'weapon',
                'bow',
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
                'bow',
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
            ],
            'locations' => Location::select('name', 'id')->get(),
        ];
    }

    public function cleanRequestData(array $params): array {
        if ($params['type'] !== 'quest') {
            $params['effect'] = null;
        }

        if (!isset($params['can_use_on_other_items'])) {
            $params['can_use_on_other_items'] = false;
            $params['holy_level'] = null;
        }

        if (!isset($params['usable'])) {
            $params['usable']             = false;
            $params['lasts_for']          = null;
            $params['damages_kingdoms']   = false;
            $params['stat_increase']      = null;
            $params['affects_skill_type'] = null;
            $params['gold_dust_cost']     = 0;
            $params['shards_cost']        = 0;
        }

        if (!isset($params['damages_kingdoms'])) {
            $params['damages_kingdoms'] = false;
            $params['kingdom_damage']   = null;
        }

        if ($params['damages_kingdoms']) {
            $params['lasts_for']          = null;
            $params['stat_increase']      = null;
            $params['affects_skill_type'] = null;
        }

        if (!$params['stat_increase']) {
            $params['increase_stat_by'] = 0;
        }

        if (is_null($params['affects_skill_type'])) {
            $params['increase_skill_bonus_by']          = null;
            $params['increase_skill_training_bonus_by'] = null;
        }

        if (!isset($params['can_resurrect'])) {
            $params['resurrection_chance'] = null;
        }

        if (!isset($params['can_craft'])) {
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
