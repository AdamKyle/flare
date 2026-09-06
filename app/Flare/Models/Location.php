<?php

namespace App\Flare\Models;

use App\Game\Maps\Values\LocationType;
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

    /**
     * The canonical relationship for quest Items that drop at this Location.
     */
    public function questItemDrops(): HasMany
    {
        return $this->hasMany(Item::class, 'drop_location_id', 'id')
            ->where('type', 'quest');
    }

    /**
     * Scope Locations to only those with at least one quest Item drop.
     */
    public function scopeDropsQuestItems(Builder $query): Builder
    {
        return $query->whereHas('questItemDrops');
    }

    /**
     * Scope Locations to those eligible to receive a Location Gem parameter: they must have a
     * quest Item drop or a Location Type, must not be a Weekly Fight or Cave of Memories
     * Location, and must not belong to a generated Gem World Map.
     */
    public function scopeEligibleForLocationGems(Builder $query): Builder
    {
        $excludedTypes = array_merge(
            LocationType::weeklyFightLocationTypes(),
            [LocationType::CAVE_OF_MEMORIES->value]
        );

        return $query->where(function (Builder $query) {
            $query->whereHas('questItemDrops')
                ->orWhereNotNull('type');
        })
            ->where(function (Builder $query) use ($excludedTypes) {
                $query->whereNull('type')->orWhereNotIn('type', $excludedTypes);
            })
            ->whereHas('map', fn (Builder $mapQuery) => $mapQuery->whereNull('generated_map_type'));
    }

    /**
     * Build the Location's display name including its Map name.
     */
    public function getNameWithMapAttribute(): string
    {
        return $this->name.' ('.$this->map->name.')';
    }

    /**
     * Build the Location's display name including its special type and plane name for Location Gem selection.
     */
    public function getNameWithPlaneForLocationGemAttribute(): string
    {
        $planeName = $this->map?->name ?? '';

        if (! is_null($this->type)) {
            $typeName = LocationType::getNamedValues()[$this->type] ?? $this->type;

            return $this->name.' [Special Type: '.$typeName.'] ('.$planeName.')';
        }

        return $this->name.' ('.$planeName.')';
    }

    /**
     * The quest Item awarded for visiting this Location.
     */
    public function questRewardItem(): HasOne
    {
        return $this->hasOne(Item::class, 'id', 'quest_reward_item_id');
    }

    /**
     * The Game Map this Location belongs to.
     */
    public function map(): HasOne
    {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    /**
     * The Raid associated with this Location, when one is set.
     */
    public function raid(): HasOne
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    /**
     * The Location Gem parameters configured for this Location.
     */
    public function gemParamters(): HasOne
    {
        return $this->hasOne(GameLocationGemParamter::class);
    }

    /**
     * The quest Item required to be handed in for quests tied to this Location.
     */
    public function requiredQuestItem(): HasOne
    {
        return $this->hasOne(Item::class, 'id', 'required_quest_item_id');
    }

    /**
     * Resolve the Location's current type enum case, when one is set.
     */
    public function locationType(): ?LocationType
    {
        if (is_null($this->type)) {
            return null;
        }

        return LocationType::tryFrom($this->type);
    }

    /**
     * Resolve the factory used to build new Location instances.
     */
    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    /**
     * Register model lifecycle hooks that invalidate the cached map-locations list.
     */
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
