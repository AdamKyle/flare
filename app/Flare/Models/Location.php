<?php

namespace App\Flare\Models;

use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\LocationFactory;

class Location extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'game_map_id',
        'quest_reward_item_id',
        'required_quest_item_id',
        'description',
        'is_port',
        'can_players_enter',
        'enemy_strength_type',
        'can_auto_battle',
        'x',
        'y',
        'type',
        'raid_id',
        'has_raid_boss',
        'is_corrupted',
        'pin_css_class',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'y'                      => 'integer',
        'x'                      => 'integer',
        'type'                   => 'integer',
        'is_port'                => 'boolean',
        'can_players_enter'      => 'boolean',
        'can_auto_battle'        => 'boolean',
        'game_map_id'            => 'integer',
        'quest_reward_item_id'   => 'integer',
        'required_quest_item_id' => 'integer',
        'enemy_strength_type'    => 'integer',
        'raid_id'                => 'integer',
        'has_raid_boss'          => 'boolean',
        'is_corrupted'           => 'boolean',
    ];

    public function questRewardItem() {
        return $this->hasOne(Item::class, 'id', 'quest_reward_item_id');
    }

    public function map() {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    public function raid() {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function requiredQuestItem() {
        return $this->hasOne(Item::class, 'id', 'required_quest_item_id');
    }

    public function locationType() {
        return new LocationType($this->type);
    }

    /**
     * Return the drop chance for a location.
     *
     * Locations must have an Effect Value.
     *
     * @return float
     * @throws \Exception
     */
    public function getDropChance(): float {

        if (is_null($this->enemy_strength_type)) {
            return 0.0;
        }

        return (new LocationEffectValue($this->enemy_strength_type))->fetchDropRate();
    }

    protected static function newFactory() {
        return LocationFactory::new();
    }
}
