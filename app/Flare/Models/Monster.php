<?php

namespace App\Flare\Models;

use Database\Factories\MonsterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    use HasFactory;

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
        'game_map_id',
        'is_celestial_entity',
        'gold_cost',
        'gold_dust_cost',
        'can_cast',
        'max_spell_damage',
        'max_affix_damage',
        'spell_evasion',
        'affix_resistance',
        'healing_percentage',
        'entrancing_chance',
        'devouring_light_chance',
        'devouring_darkness_chance',
        'ambush_chance',
        'ambush_resistance',
        'counter_chance',
        'counter_resistance',
        'accuracy',
        'casting_accuracy',
        'dodge',
        'criticality',
        'shards',
        'celestial_type',
        'is_raid_monster',
        'is_raid_boss',
        'raid_special_attack_type',
        'fire_atonement',
        'ice_atonement',
        'water_atonement',
        'life_stealing_resistance',
        'only_for_location_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'xp' => 'integer',
        'str' => 'integer',
        'dur' => 'integer',
        'dex' => 'integer',
        'chr' => 'integer',
        'int' => 'integer',
        'ac' => 'integer',
        'gold' => 'integer',
        'celestial_type' => 'integer',
        'drop_check' => 'float',
        'max_level' => 'integer',
        'quest_item_drop_chance' => 'float',
        'is_celestial_entity' => 'boolean',
        'gold_cost' => 'integer',
        'gold_dust_cost' => 'integer',
        'can_cast' => 'boolean',
        'can_use_artifacts' => 'boolean',
        'max_spell_damage' => 'integer',
        'max_affix_damage' => 'integer',
        'shards' => 'integer',
        'spell_evasion' => 'float',
        'affix_resistance' => 'float',
        'healing_percentage' => 'float',
        'entrancing_chance' => 'float',
        'devouring_light_chance' => 'float',
        'devouring_darkness_chance' => 'float',
        'accuracy' => 'float',
        'casting_accuracy' => 'float',
        'dodge' => 'float',
        'criticality' => 'float',
        'ambush_chance' => 'float',
        'ambush_resistance' => 'float',
        'counter_chance' => 'float',
        'counter_resistance' => 'float',
        'fire_atonement' => 'float',
        'ice_atonement' => 'float',
        'water_atonement' => 'float',
        'is_raid_monster' => 'boolean',
        'is_raid_boss' => 'boolean',
        'raid_special_attack_type' => 'integer',
        'life_stealing_resistance' => 'float',
        'only_for_location_type' => 'integer',
    ];

    public function questItem()
    {
        return $this->hasOne(Item::class, 'id', 'quest_item_id');
    }

    public function gameMap()
    {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    protected static function newFactory()
    {
        return MonsterFactory::new();
    }
}
