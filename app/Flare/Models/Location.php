<?php

namespace App\Flare\Models;

use App\Flare\Values\LocationType;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

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
        'can_auto_battle',
        'x',
        'y',
        'type',
        'raid_id',
        'has_raid_boss',
        'is_corrupted',
        'pin_css_class',
        'hours_to_drop',
        'minutes_between_delve_fights',
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
        'hours_to_drop' => 'integer',
        'raid_id' => 'integer',
        'has_raid_boss' => 'boolean',
        'is_corrupted' => 'boolean',
        'minutes_between_delve_fights' => 'integer',
    ];

    public function questItemDrops(): HasMany
    {
        return $this->hasMany(Item::class, 'drop_location_id', 'id')
            ->where('type', 'quest');
    }

    public function scopeDropsQuestItems(Builder $query): Builder
    {
        return $query->whereHas('questItemDrops');
    }

    public function scopeEligibleForLocationGems(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereHas('questItemDrops')
                ->orWhereNotNull('type');
        });
    }

    public function getNameWithMapAttribute(): string
    {
        return $this->name.' ('.$this->map->name.')';
    }

    public function getNameWithPlaneForLocationGemAttribute(): string
    {
        $planeName = $this->map?->name ?? '';

        if (! is_null($this->type)) {
            $typeName = LocationType::getNamedValues()[$this->type] ?? (string) $this->type;

            return $this->name.' [Special Type: '.$typeName.'] ('.$planeName.')';
        }

        return $this->name.' ('.$planeName.')';
    }

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

    public function gemParamters(): HasOne
    {
        return $this->hasOne(GameLocationGemParamter::class);
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

    public function locationQuestItems()
    {
        return $this->hasMany(Item::class, 'id', 'drop_location_id');
    }

    protected static function newFactory()
    {
        return LocationFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(function (Location $location): void {
            Cache::forget('map-locations-'.$location->game_map_id);
        });

        static::deleted(function (Location $location): void {
            Cache::forget('map-locations-'.$location->game_map_id);
        });
    }
}
