<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;

class BaseTransformer extends TransformerAbstract
{
    public function fetchAttackTypes(Character $character): array
    {
        $cache = Cache::get('character-attack-data-'.$character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['attack_types'];
    }

    public function fetchStats(Character $character, string $stat): mixed
    {
        $cache = Cache::get('character-attack-data-'.$character->id);

        if (is_null($cache)) {
            return 0.0;
        }

        return $cache['character_data'][$stat];
    }

    public function fetchStatAffixes(Character $character): array
    {
        $cache = Cache::get('character-attack-data-'.$character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['stat_affixes'];
    }

    public function fetchSkills(Character $character): array
    {
        $cache = Cache::get('character-attack-data-'.$character->id);

        if (is_null($cache)) {
            return [];
        }

        $skills = $cache['skills'];

        sort($skills);

        return $skills;
    }

    public function isAlchemyLocked(Character $character)
    {
        $alchemy = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        if (is_null($alchemy)) {
            return true;
        }

        $skill = Skill::where('game_skill_id', $alchemy->id)->where('character_id', $character->id)->first();

        if (! is_null($skill)) {
            return $skill->is_locked;
        }

        return true;
    }

    public function getMaxLevel(Character $character)
    {
        $item = Item::where('effect', ItemEffectsValue::CONTINUE_LEVELING)->first();

        if (is_null($item)) {
            return MaxLevel::MAX_LEVEL;
        }

        $inventory = Inventory::where('character_id', $character->id)->first();
        $slot = InventorySlot::where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (! is_null($slot)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }
}
