<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameMapFactory;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;

class GameMap extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'path',
        'default',
        'kingdom_color',
        'xp_bonus',
        'skill_training_bonus',
        'drop_chance_bonus',
        'enemy_stat_bonus',
        'character_attack_reduction',
        'required_location_id',
        'only_during_event_type',
        'can_traverse',
    ];

    protected $casts = [
        'default'                    => 'boolean',
        'xp_bonus'                   => 'float',
        'skill_training_bonus'       => 'float',
        'drop_chance_bonus'          => 'float',
        'enemy_stat_bonus'           => 'float',
        'character_attack_reduction' => 'float',
        'only_during_event_type'     => 'integer',
        'can_traverse'               => 'boolean',
    ];

    protected $appends = [
        'map_required_item',
    ];

    public function maps() {
        return $this->hasMany(Map::class, 'game_map_id', 'id');
    }

    public function requiredLocation() {
        return $this->hasOne(Location::class, 'id', 'required_location_id');
    }

    public function mapType(): MapNameValue {
        return new MapNameValue($this->name);
    }

    public function mapHasBonuses() {
        $hasBonuses = false;

        if (!is_null($this->xp_bonus) || !is_null($this->skill_training_bonus)
            || !is_null($this->drop_chance_bonus) || !is_null($this->enemy_stat_bonus)
        ) {
            $hasBonuses = true;
        }

        return $hasBonuses;
    }

    public function getMapRequiredItemAttribute() {
        switch ($this->name) {
            case 'Labyrinth':
                return Item::where('effect', ItemEffectsValue::LABYRINTH)->first();
            case 'Dungeons':
                return Item::where('effect', ItemEffectsValue::DUNGEON)->first();
            case 'Shadow Plane':
                return Item::where('effect', ItemEffectsValue::SHADOWPLANE)->first();
            case 'Hell':
                return Item::where('effect', ItemEffectsValue::HELL)->first();
            case 'Purgatory':
                return Item::where('effect', ItemEffectsValue::PURGATORY)->first();
            case 'Surface':
            default:
                return null;
        }
    }

    protected static function newFactory() {
        return GameMapFactory::new();
    }
}
