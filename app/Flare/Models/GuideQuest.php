<?php

namespace App\Flare\Models;

use App\Game\Events\Values\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GuideQuestFactory;
use App\Game\Skills\Values\SkillTypeValue;

class GuideQuest extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'intro_text',
        'instructions',
        'desktop_instructions',
        'mobile_instructions',
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
        'required_kingdom_building_id',
        'required_kingdom_building_level',
        'required_passive_skill',
        'required_passive_level',
        'required_skill_type',
        'required_skill_type_level',
        'required_class_specials_equipped',
        'required_class_rank_level',
        'required_gold',
        'required_gold_dust',
        'required_shards',
        'required_copper_coins',
        'required_gold_bars',
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
        'xp_reward',
        'parent_id',
        'unlock_at_level',
        'only_during_event',
        'be_on_game_map',
        'required_event_goal_participation',
        'required_holy_stacks',
        'required_attached_gems',
        'required_specialty_type',
        'must_be_pledged_to_faction',
        'must_be_assisting_npc',
        'required_fame_level',
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
        'required_kingdom_building_id'       => 'integer',
        'required_kingdom_building_level'    => 'integer',
        'required_passive_skill'             => 'integer',
        'required_passive_level'             => 'integer',
        'required_skill_type'                => 'integer',
        'required_skill_type_level'          => 'integer',
        'required_class_specials_equipped'   => 'integer',
        'required_class_rank_level'          => 'integer',
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
        'required_gold_bars'                 => 'integer',
        'required_copper_coins'              => 'integer',
        'gold_reward'                        => 'integer',
        'gold_dust_reward'                   => 'integer',
        'shards_reward'                      => 'integer',
        'faction_points_per_kill'            => 'integer',
        'xp_reward'                          => 'integer',
        'parent_id'                          => 'integer',
        'unlock_at_level'                    => 'integer',
        'only_during_event'                  => 'integer',
        'be_on_game_map'                     => 'integer',
        'required_event_goal_participation'  => 'integer',
        'required_holy_stacks'               => 'integer',
        'required_attached_gems'             => 'integer',
        'required_fame_level'                => 'integer',
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
        'kingdom_building_name',
        'parent_quest_name',
        'required_to_be_on_game_map_name',
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
        $gameMap = GameMap::find($this->required_faction_id);

        if (!is_null($gameMap)) {
            return $gameMap->name;
        }

        return null;
    }

    public function getGameMapNameAttribute() {
        $gameMap = GameMap::find($this->required_game_map_id);

        if (!is_null($gameMap)) {
            return $gameMap->name;
        }

        return null;
    }

    public function getKingdomBuildingNameAttribute() {
        $gameBuilding = GameBuilding::find($this->required_kingdom_building_id);

        if (!is_null($gameBuilding)) {
            return $gameBuilding->name;
        }

        return null;
    }

    public function getParentQuestNameAttribute() {
        $parentQuest = GuideQuest::find($this->parent_id);

        if (is_null($parentQuest)) {
            return null;
        }

        return $parentQuest->name;
    }

    public function getRequiredToBeOnGameMapNameAttribute() {
        if (is_null($this->be_on_game_map)) {
            return null;
        }

        return GameMap::find($this->be_on_game_map)->name;
    }

    public function eventType(): EventType | null {

        if (is_null($this->only_during_event)) {
            return null;
        }

        return new EventType($this->only_during_event);
    }

    protected static function newFactory() {
        return GuideQuestFactory::new();
    }
}
