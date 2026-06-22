<?php

namespace App\Flare\Models;

use App\Game\Gems\Values\GemTierValue;
use App\Game\Gems\Values\GemTypeValue;
use Database\Factories\GemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'primary_atonement_type',
        'secondary_atonement_type',
        'tertiary_atonement_type',
        'primary_atonement_amount',
        'secondary_atonement_amount',
        'tertiary_atonement_amount',
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
        'ascended_item_drop_chance_increase',
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
        'faction_point_increase',
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
        'primary_atonement_type' => 'integer',
        'secondary_atonement_type' => 'integer',
        'tertiary_atonement_type' => 'integer',
        'primary_atonement_amount' => 'float',
        'secondary_atonement_amount' => 'float',
        'tertiary_atonement_amount' => 'float',
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
        'ascended_item_drop_chance_increase' => 'float',
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
        'faction_point_increase' => 'float',
        'monster_atonement' => 'integer',
        'monster_atonement_amount' => 'float',
    ];

    public function scopeCharacter(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_CHARACTER);
    }

    public function scopeMap(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_MAP);
    }

    public function scopeLocation(Builder $query): Builder
    {
        return $query->where('domain', self::DOMAIN_LOCATION);
    }

    public function rolledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_by_user_id');
    }

    public function gameMapGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameMapGemParamter::class, 'game_map_gem_paramters_id');
    }

    public function gameLocationGemParamter(): BelongsTo
    {
        return $this->belongsTo(GameLocationGemParamter::class, 'game_location_gem_paramters_id');
    }

    public function primaryAtonement(): GemTypeValue
    {
        return new GemTypeValue($this->primary_atonement_type);
    }

    public function secondaryAtonementType(): GemTypeValue
    {
        return new GemTypeValue($this->secondary_atonement_type);
    }

    public function tertiaryAtonementType(): GemTypeValue
    {
        return new GemTypeValue($this->tertiary_atonement_type);
    }

    public function gemTier(): GemTierValue
    {
        return new GemTierValue($this->tier);
    }

    protected static function newFactory()
    {
        return GemFactory::new();
    }
}
