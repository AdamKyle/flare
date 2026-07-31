<?php

namespace App\Flare\Models;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use Database\Factories\GameMapFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameMap extends Model
{
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
        'generated_map_type',
        'generated_parent_game_map_id',
        'game_map_gem_paramter_id',
        'game_location_gem_paramter_id',
    ];

    protected $casts = [
        'default' => 'boolean',
        'xp_bonus' => 'float',
        'skill_training_bonus' => 'float',
        'drop_chance_bonus' => 'float',
        'enemy_stat_bonus' => 'float',
        'character_attack_reduction' => 'float',
        'only_during_event_type' => 'integer',
        'can_traverse' => 'boolean',
        'generated_parent_game_map_id' => 'integer',
        'game_map_gem_paramter_id' => 'integer',
        'game_location_gem_paramter_id' => 'integer',
    ];

    protected $appends = [
        'map_required_item',
    ];

    public function maps()
    {
        return $this->hasMany(Map::class, 'game_map_id', 'id');
    }

    public function requiredLocation()
    {
        return $this->hasOne(Location::class, 'id', 'required_location_id');
    }

    public function gemParamters(): HasOne
    {
        return $this->hasOne(GameMapGemParamter::class);
    }

    public function generatedParentMap(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'generated_parent_game_map_id');
    }

    public function generatedMapGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameMapGemParamter::class, 'game_map_gem_paramter_id');
    }

    public function generatedLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class, 'game_location_gem_paramter_id');
    }

    public function mapType(): MapNameValue
    {
        return new MapNameValue($this->effectiveGameMap()->name);
    }

    public function isGeneratedGemMap(): bool
    {
        return ! is_null($this->generated_map_type);
    }

    public function effectiveGameMap(): GameMap
    {
        if (! is_null($this->generated_parent_game_map_id)) {
            return $this->generatedParentMap ?? $this;
        }

        return $this;
    }

    public function monsterSourceGameMap(): GameMap
    {
        return $this->effectiveGameMap();
    }

    public function mapHasBonuses()
    {
        $hasBonuses = false;

        if (! is_null($this->xp_bonus) || ! is_null($this->skill_training_bonus)
            || ! is_null($this->drop_chance_bonus) || ! is_null($this->enemy_stat_bonus)
        ) {
            $hasBonuses = true;
        }

        return $hasBonuses;
    }

    public function getMapRequiredItemAttribute()
    {
        switch ($this->effectiveGameMap()->name) {
            case 'Labyrinth':
                return Item::where('effect', ItemEffectsValue::LABYRINTH)->first();
            case 'Dungeons':
                return Item::where('effect', ItemEffectsValue::DUNGEON)->first();
            case 'Shadow Plane':
                return Item::where('effect', ItemEffectsValue::SHADOW_PLANE)->first();
            case 'Hell':
                return Item::where('effect', ItemEffectsValue::HELL)->first();
            case 'Purgatory':
                return Item::where('effect', ItemEffectsValue::PURGATORY)->first();
            case 'Twisted Memories':
                return Item::where('effect', ItemEffectsValue::TWISTED_TREE_BRANCH)->first();
            case 'Surface':
            default:
                return null;
        }
    }

    protected static function newFactory()
    {
        return GameMapFactory::new();
    }
}
