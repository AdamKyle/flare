<?php

namespace App\Flare\Models;

use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\GemAbilityScalingSource;
use App\Game\Gems\Values\GemAbilityType;
use Database\Factories\GameGemAbilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameGemAbility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ability_type',
        'effect_type',
        'attack_types',
        'proc_chance',
        'effect_value',
        'scaling_source',
        'enabled',
    ];

    protected $casts = [
        'attack_types' => 'array',
        'proc_chance' => 'float',
        'effect_value' => 'float',
        'enabled' => 'boolean',
        'ability_type' => GemAbilityType::class,
        'effect_type' => GemAbilityEffectType::class,
        'scaling_source' => GemAbilityScalingSource::class,
    ];

    /**
     * Get the character Gem modifier rows that rolled this Gem Ability.
     *
     * @return HasMany
     */
    public function characterGemModifiers(): HasMany
    {
        return $this->hasMany(CharacterGemModifier::class);
    }

    /**
     * Get the factory instance for this model.
     *
     * @return GameGemAbilityFactory
     */
    protected static function newFactory(): GameGemAbilityFactory
    {
        return GameGemAbilityFactory::new();
    }
}
