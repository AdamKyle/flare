<?php

namespace App\Flare\Models;

use App\Game\Mercenaries\Values\MercenaryValue;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Game\Skills\Values\SkillTypeValue;

class GuideQuest extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'intro_text',
        'instructions',
        'required_level',
        'required_skill',
        'required_skill_level',
        'required_secondary_skill',
        'required_secondary_skill_level',
        'required_faction_id',
        'required_faction_level',
        'required_game_map_id',
        'required_quest_id',
        'required_quest_item_id',
        'secondary_quest_item_id',
        'required_kingdoms',
        'required_kingdom_level',
        'required_kingdom_units',
        'required_passive_skill',
        'required_passive_level',
        'required_skill_type',
        'required_skill_type_level',
        'required_mercenary_type',
        'required_secondary_mercenary_type',
        'required_mercenary_level',
        'required_secondary_mercenary_level',
        'required_class_specials_equipped',
        'required_gold',
        'required_gold_dust',
        'required_shards',
        'required_stats',
        'required_str',
        'required_dex',
        'required_int',
        'required_dur',
        'required_chr',
        'required_agi',
        'required_focus',
        'faction_points_per_kill',
        'gold_dust_reward',
        'shards_reward',
        'gold_reward',
        'xp_reward'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required_level'                     => 'integer',
        'required_skill'                     => 'integer',
        'required_skill_level'               => 'integer',
        'required_required_skill'            => 'integer',
        'required_required_skill_level'      => 'integer',
        'required_faction_id'                => 'integer',
        'required_faction_level'             => 'integer',
        'required_game_map_id'               => 'integer',
        'required_quest_id'                  => 'integer',
        'required_quest_item_id'             => 'integer',
        'secondary_quest_item_id'            => 'integer',
        'required_kingdoms'                  => 'integer',
        'required_kingdom_level'             => 'integer',
        'required_kingdom_units'             => 'integer',
        'required_passive_skill'             => 'integer',
        'required_passive_level'             => 'integer',
        'required_skill_type'                => 'integer',
        'required_skill_type_level'          => 'integer',
        'required_mercenary_type'            => 'string',
        'required_secondary_mercenary_type'  => 'string',
        'required_mercenary_level'           => 'integer',
        'required_secondary_mercenary_level' => 'integer',
        'required_class_specials_equipped'   => 'integer',
        'required_stats'                     => 'integer',
        'required_str'                       => 'integer',
        'required_dex'                       => 'integer',
        'required_int'                       => 'integer',
        'required_dur'                       => 'integer',
        'required_chr'                       => 'integer',
        'required_agi'                       => 'integer',
        'required_focus'                     => 'integer',
        'required_gold'                      => 'integer',
        'required_gold_dust'                 => 'integer',
        'required_shards'                    => 'integer',
        'gold_reward'                        => 'integer',
        'gold_dust_reward'                   => 'integer',
        'shards_reward'                      => 'integer',
        'faction_points_per_kill'            => 'integer',
        'xp_reward'                          => 'integer', 
    ];

    protected $appends = [
        'skill_name',
        'faction_name',
        'game_map_name',
        'quest_name',
        'quest_item_name',
        'secondary_quest_item_name',
        'passive_name',
        'secondary_skill_name',
        'skill_type_name',
        'mercenary_name',
        'secondary_mercenary_name',
    ];

    public function getSkillNameAttribute() {
        $skill = GameSkill::find($this->required_skill);

        if (!is_null($skill)) {
            return $skill->name;
        }

        return null;
    }

    public function getSkillTypeNameAttribute() {
        if (is_null($this->required_skill_type)) {
            return null;
        }

        return SkillTypeValue::$namedValues[$this->required_skill_type];
    }

    public function getSecondarySkillNameAttribute() {
        $skill = GameSkill::find($this->required_secondary_skill);

        if (!is_null($skill)) {
            return $skill->name;
        }

        return null;
    }

    public function getMercenaryNameAttribute() {

        if (is_null($this->required_mercenary_type)) {
            return null;
        }

        try {
            return (new MercenaryValue($this->required_mercenary_type))->getName();
        } catch (Exception $e) {
            Log::info('Invalid Mercenary for: ' . $this->required_mercenary_type);
            
            return null;
        }
    }

    public function getSecondaryMercenaryNameAttribute() {

        if (is_null($this->required_mercenary_type)) {
            return null;
        }

        try {
            return (new MercenaryValue($this->required_secondary_mercenary_type))->getName();
        } catch (Exception $e) {
            Log::info('Invalid Mercenary for: ' . $this->required_secondary_mercenary_type);
            
            return null;
        }
    }

    public function getQuestNameAttribute() {
        $quest = Quest::find($this->required_quest_id);

        if (!is_null($quest)) {
            return $quest->name;
        }

        return null;
    }

    public function getPassiveNameAttribute() {
        $passive = PassiveSkill::find($this->required_passive_skill);

        if (!is_null($passive)) {
            return $passive->name;
        }

        return null;
    }

    public function getQuestItemNameAttribute() {
        $questItem = Item::where('type', 'quest')->where('id', $this->required_quest_item_id)->first();

        if (!is_null($questItem)) {
            return $questItem->affix_name;
        }

        return null;
    }

    public function getSecondaryQuestItemNameAttribute() {
        $questItem = Item::where('type', 'quest')->where('id', $this->secondary_quest_item_id)->first();

        if (!is_null($questItem)) {
            return $questItem->affix_name;
        }

        return null;
    }

    public function getFactionNameAttribute() {
        return $this->getGameMapNameAttribute();
    }

    public function getGameMapNameAttribute() {
        $gameMap = GameMap::find($this->required_faction_id);

        if (!is_null($gameMap)) {
            return $gameMap->name;
        }

        return null;
    }
}
