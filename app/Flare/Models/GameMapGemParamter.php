<?php

namespace App\Flare\Models;

use Database\Factories\GameMapGemParamterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameMapGemParamter extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_map_id',
        'name',
        'description',
        'character_xp_bonus_range',
        'character_class_rank_xp_bonus_range',
        'kingdom_passive_training_reduction_range',
        'gold_gain_range',
        'gold_dust_gain_range',
        'shards_gain_range',
        'copper_coin_gain_range',
        'character_class_specialty_xp_gain_range',
        'crafting_skill_ids',
        'crafting_skill_bonus_range',
        'item_drop_chance_increase_range',
        'unique_item_drop_chance_increase_range',
        'mythic_item_drop_chance_increase_range',
        'cosmic_item_drop_chance_increase_range',
        'character_power_reduction_range',
        'enemy_strength_increase_range',
        'enemy_healing_increase_range',
        'enemy_spell_evasion_range',
        'enemy_affix_resistance_range',
        'enemy_entrancing_chance_range',
        'enemy_devouring_light_chance_range',
        'enemy_devouring_darkness_chance_range',
        'enemy_ambush_chance_range',
        'enemy_ambush_resistance_range',
        'enemy_counter_chance_range',
        'enemy_counter_resistance_range',
        'enemy_quest_item_drop_chance_increase_range',
        'monster_xp_increase_range',
        'monster_gold_drop_increase_range',
        'monster_atonement',
        'monster_atonement_range',
        'rolled_gem_id',
        'roll_count',
    ];

    protected $casts = [
        'game_map_id' => 'integer',
        'crafting_skill_ids' => 'array',
        'monster_atonement' => 'integer',
        'rolled_gem_id' => 'integer',
        'roll_count' => 'integer',
    ];

    /**
     * Get the parent Game Map this Gem profile belongs to.
     */
    public function gameMap(): BelongsTo
    {
        return $this->belongsTo(GameMap::class);
    }

    /**
     * Get the currently active rolled Gem for this profile.
     */
    public function rolledGem(): BelongsTo
    {
        return $this->belongsTo(Gem::class, 'rolled_gem_id');
    }

    /**
     * Get every Gem roll ever created for this profile, active and historical.
     */
    public function gemRolls(): HasMany
    {
        return $this->hasMany(Gem::class, 'game_map_gem_paramters_id');
    }

    /**
     * Get the generated Gem World Game Map produced from this profile, when one exists.
     */
    public function generatedMap(): HasOne
    {
        return $this->hasOne(GameMap::class, 'game_map_gem_paramter_id');
    }

    /**
     * Get the shared global Gem progression for this profile.
     */
    public function progression(): HasOne
    {
        return $this->hasOne(GameMapGemProgression::class);
    }

    /**
     * Get every Character's personal Gem progression for this profile.
     */
    public function characterProgressions(): HasMany
    {
        return $this->hasMany(CharacterGameMapGemProgression::class);
    }

    /**
     * Get every active Character Gem Scroll currently applied to this profile.
     */
    public function activeCharacterScrolls(): HasMany
    {
        return $this->hasMany(CharacterGameMapGemScroll::class);
    }

    /**
     * Return the fillable range field names this profile can roll a Gem value from.
     */
    public function rollableRangeFields(): array
    {
        return array_values(array_filter(
            array_keys($this->getAttributes()),
            fn (string $attribute): bool => str_ends_with($attribute, '_range')
                && $attribute !== 'monster_atonement_range',
        ));
    }

    /**
     * Get the factory instance for this model.
     */
    protected static function newFactory(): GameMapGemParamterFactory
    {
        return GameMapGemParamterFactory::new();
    }
}
