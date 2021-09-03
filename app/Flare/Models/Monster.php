<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\MonsterFactory;
use App\Flare\Models\Skill;
use App\Flare\Models\Traits\WithSearch;

class Monster extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'damage_stat',
        'xp',
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'agi',
        'focus',
        'ac',
        'gold',
        'max_level',
        'health_range',
        'attack_range',
        'drop_check',
        'quest_item_id',
        'quest_item_drop_chance',
        'published',
        'game_map_id',
        'is_celestial_entity',
        'gold_cost',
        'gold_dust_cost',
        'can_cast',
        'can_use_artifacts',
        'max_spell_damage',
        'max_artifact_damage',
        'spell_evasion',
        'artifact_annulment',
        'shards',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'xp'                     => 'integer',
        'str'                    => 'integer',
        'dur'                    => 'integer',
        'dex'                    => 'integer',
        'chr'                    => 'integer',
        'int'                    => 'integer',
        'ac'                     => 'integer',
        'gold'                   => 'integer',
        'drop_check'             => 'float',
        'max_level'              => 'integer',
        'quest_item_drop_chance' => 'float',
        'published'              => 'boolean',
        'is_celestial_entity'    => 'boolean',
        'gold_cost'              => 'integer',
        'gold_dust_cost'         => 'integer',
        'can_cast'               => 'boolean',
        'can_use_artifacts'      => 'boolean',
        'max_spell_damage'       => 'integer',
        'max_artifact_damage'    => 'integer',
        'shards'                 => 'integer',
        'spell_evasion'          => 'decimal:4',
        'artifact_annulment'     => 'decimal:4',
    ];

    public function skills() {
        return $this->hasMany(Skill::class);
    }

    public function questItem() {
        return $this->hasOne(Item::class, 'id', 'quest_item_id');
    }

    public function gameMap() {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    protected static function newFactory() {
        return MonsterFactory::new();
    }

}
