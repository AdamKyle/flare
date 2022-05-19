<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CharacterInCelestialFightFactory;

class GuideQuest extends Model
{
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
        'required_faction_id',
        'required_faction_level',
        'required_game_map_id',
        'required_quest_id',
        'required_quest_item_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required_level'         => 'integer',
        'required_skill'         => 'integer',
        'required_skill_level'   => 'integer',
        'required_faction_id'    => 'integer',
        'required_faction_level' => 'integer',
        'required_game_map_id'   => 'integer',
        'required_quest_id'      => 'integer',
        'required_quest_item_id' => 'integer',
    ];

    protected $appends = [
        'skill_name',
        'faction_name',
        'game_map_name',
        'quest_name',
        'quest_item_name',
    ];

    public function getSkillNameAttribute() {
        $skill = GameSkill::find($this->required_skill);

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

    public function getQuestItemNameAttribute() {
        $questItem = Item::where('type', 'quest')->where('id', $this->quest_item_id)->first();

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