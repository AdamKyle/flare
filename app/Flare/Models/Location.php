<?php

namespace App\Flare\Models;

use App\Flare\Values\LocationType;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
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
        'enemy_strength_increase',
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
        'y' => 'integer',
        'x' => 'integer',
        'type' => 'integer',
        'is_port' => 'boolean',
        'can_players_enter' => 'boolean',
        'can_auto_battle' => 'boolean',
        'game_map_id' => 'integer',
        'quest_reward_item_id' => 'integer',
        'required_quest_item_id' => 'integer',
        'enemy_strength_increase' => 'float',
        'raid_id' => 'integer',
        'has_raid_boss' => 'boolean',
        'is_corrupted' => 'boolean',
    ];

    public function questRewardItem()
    {
        return $this->hasOne(Item::class, 'id', 'quest_reward_item_id');
    }

    public function map()
    {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function requiredQuestItem()
    {
        return $this->hasOne(Item::class, 'id', 'required_quest_item_id');
    }

    public function locationType()
    {
        if (is_null($this->type)) {
            return null;
        }

        return new LocationType($this->type);
    }

    public function locationQuestItems() {
        return $this->hasMany(Item::class, 'id', 'drop_location_id');
    }

    protected static function newFactory()
    {
        return LocationFactory::new();
    }
}
