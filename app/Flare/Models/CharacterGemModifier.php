<?php

namespace App\Flare\Models;

use App\Game\Gems\Values\CharacterGemModifierType;
use Database\Factories\CharacterGemModifierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CharacterGemModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'gem_id',
        'roll_position',
        'modifier_type',
        'amount',
        'game_gem_ability_id',
    ];

    protected $casts = [
        'gem_id' => 'integer',
        'roll_position' => 'integer',
        'modifier_type' => CharacterGemModifierType::class,
        'amount' => 'float',
        'game_gem_ability_id' => 'integer',
    ];

    /**
     * Register persistence guards for ability and numeric modifier rows.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function (CharacterGemModifier $modifier): void {
            $isAbility = $modifier->modifier_type === CharacterGemModifierType::GEM_ABILITY;

            if ($isAbility && (is_null($modifier->game_gem_ability_id) || ! is_null($modifier->amount))) {
                throw new LogicException('Gem Ability modifiers require an ability and cannot contain an amount.');
            }

            if (! $isAbility && (is_null($modifier->amount) || ! is_null($modifier->game_gem_ability_id))) {
                throw new LogicException('Numeric Gem modifiers require an amount and cannot reference an ability.');
            }
        });
    }

    /**
     * Get the character Gem this modifier was rolled on.
     *
     * @return BelongsTo
     */
    public function gem(): BelongsTo
    {
        return $this->belongsTo(Gem::class);
    }

    /**
     * Get the Gem Ability definition this modifier rolled, when it is a Gem Ability modifier.
     *
     * @return BelongsTo
     */
    public function gameGemAbility(): BelongsTo
    {
        return $this->belongsTo(GameGemAbility::class);
    }

    /**
     * Get the factory instance for this model.
     *
     * @return CharacterGemModifierFactory
     */
    protected static function newFactory(): CharacterGemModifierFactory
    {
        return CharacterGemModifierFactory::new();
    }
}
