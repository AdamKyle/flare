<?php

namespace App\Flare\Models;

use App\Game\Gems\Values\GemTierValue;
use Database\Factories\GemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gem extends Model
{
    use HasFactory;

    public const DOMAIN_CHARACTER = 'character';

    public const DOMAIN_MAP = 'map';

    public const DOMAIN_LOCATION = 'location';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'tier',
        'domain',
        'rolled_by_user_id',
        'roll_number',
        'game_map_gem_paramters_id',
        'game_location_gem_paramters_id',
        'character_xp_bonus',
        'character_class_rank_xp_bonus',
        'kingdom_passive_training_reduction',
        'gold_gain',
        'gold_dust_gain',
        'shards_gain',
        'copper_coin_gain',
        'character_class_specialty_xp_gain',
        'crafting_skill_ids',
        'crafting_skill_bonus',
        'item_drop_chance_increase',
        'unique_item_drop_chance_increase',
        'mythic_item_drop_chance_increase',
        'cosmic_item_drop_chance_increase',
        'character_power_reduction',
        'enemy_strength_increase',
        'enemy_healing_increase',
        'enemy_spell_evasion',
        'enemy_affix_resistance',
        'enemy_entrancing_chance',
        'enemy_devouring_light_chance',
        'enemy_devouring_darkness_chance',
        'enemy_ambush_chance',
        'enemy_ambush_resistance',
        'enemy_counter_chance',
        'enemy_counter_resistance',
        'enemy_quest_item_drop_chance_increase',
        'monster_xp_increase',
        'monster_gold_drop_increase',
        'monster_atonement',
        'monster_atonement_amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tier' => 'integer',
        'domain' => 'string',
        'rolled_by_user_id' => 'integer',
        'roll_number' => 'integer',
        'game_map_gem_paramters_id' => 'integer',
        'game_location_gem_paramters_id' => 'integer',
        'character_xp_bonus' => 'float',
        'character_class_rank_xp_bonus' => 'float',
        'kingdom_passive_training_reduction' => 'float',
        'gold_gain' => 'float',
        'gold_dust_gain' => 'float',
        'shards_gain' => 'float',
        'copper_coin_gain' => 'float',
        'character_class_specialty_xp_gain' => 'float',
        'crafting_skill_ids' => 'array',
        'crafting_skill_bonus' => 'float',
        'item_drop_chance_increase' => 'float',
        'unique_item_drop_chance_increase' => 'float',
        'mythic_item_drop_chance_increase' => 'float',
        'cosmic_item_drop_chance_increase' => 'float',
        'character_power_reduction' => 'float',
        'enemy_strength_increase' => 'float',
        'enemy_healing_increase' => 'float',
        'enemy_spell_evasion' => 'float',
        'enemy_affix_resistance' => 'float',
        'enemy_entrancing_chance' => 'float',
        'enemy_devouring_light_chance' => 'float',
        'enemy_devouring_darkness_chance' => 'float',
        'enemy_ambush_chance' => 'float',
        'enemy_ambush_resistance' => 'float',
        'enemy_counter_chance' => 'float',
        'enemy_counter_resistance' => 'float',
        'enemy_quest_item_drop_chance_increase' => 'float',
        'monster_xp_increase' => 'float',
        'monster_gold_drop_increase' => 'float',
        'monster_atonement' => 'integer',
        'monster_atonement_amount' => 'float',
    ];

    /**
     * Scope the query to character-domain Gems.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCharacter(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_CHARACTER);
    }

    /**
     * Scope the query to Map-domain Gems.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeMap(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_MAP);
    }

    /**
     * Scope the query to Location-domain Gems.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLocation(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_LOCATION);
    }

    /**
     * Get the User who rolled this World Gem.
     *
     * @return BelongsTo
     */
    public function rolledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_by_user_id');
    }

    /**
     * Get the Map Gem profile this World Gem was rolled from.
     *
     * @return BelongsTo
     */
    public function gameMapGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameMapGemParamter::class, 'game_map_gem_paramters_id');
    }

    /**
     * Get the Location Gem profile this World Gem was rolled from.
     *
     * @return BelongsTo
     */
    public function gameLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class, 'game_location_gem_paramters_id');
    }

    /**
     * Get the three rolled modifiers of this character Gem ordered by roll position.
     *
     * @return HasMany
     */
    public function characterModifiers(): HasMany
    {
        return $this->hasMany(CharacterGemModifier::class)->orderBy('roll_position');
    }

    /**
     * Return the tier value object for this Gem.
     *
     * @return GemTierValue
     */
    public function gemTier(): GemTierValue
    {
        return new GemTierValue($this->tier);
    }

    /**
     * Get the factory instance for this model.
     *
     * @return GemFactory
     */
    protected static function newFactory(): GemFactory
    {
        return GemFactory::new();
    }
}
