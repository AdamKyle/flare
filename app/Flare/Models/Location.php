<?php

namespace App\Flare\Models;

use App\Flare\Values\LocationEffectValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\LocationFactory;
use App\Flare\Models\Traits\WithSearch;

class Location extends Model
{
    use HasFactory, WithSearch;

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
        'x',
        'y',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'y'                   => 'integer',
        'x'                   => 'integer',
        'type'                => 'integer',
        'enemy_strength_type' => 'string',
        'is_port'             => 'boolean',
        'can_players_enter'   => 'boolean',
    ];

    public function questRewardItem() {
        return $this->hasOne(Item::class, 'id', 'quest_reward_item_id');
    }

    public function map() {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    public function requiredQuestItem() {
        return $this->hasOne(Item::class, 'id', 'required_quest_item_id');
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
