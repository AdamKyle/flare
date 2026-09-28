<?php

namespace App\Game\Gems\Builders;

use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Services\CharacterGemRollService;
use App\Game\Gems\Values\CharacterGemRoll;
use App\Game\Gems\Values\GemTierValue;

class GemBuilder
{
    /**
     * @var array
     */
    private array $names = [
        'Rubyvenite',
        'Senamotome',
        'Pezdcreekite',
        'Glinting Bytocchacuaite',
        'Gilty Kobritoid',
        'Haioeite',
        'Vivc',
        'Gunikahnite',
        'Beige Kratndum',
        'Cerise Domandine',
        'Todundum',
        'Pink Mangbazite',
        'Black Ulelcanthite',
        'Pharozoisite',
        'Green Moonniite,',
        'Fresckeite',
        'Espkerite',
        'Tan Abenkite',
        'Lemon Traet',
        'Badgonite',
    ];

    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param CharacterGemRollService $characterGemRollService
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly CharacterGemRollService $characterGemRollService,
    ) {}

    /**
     * Determine whether a Gem of the given tier can currently be rolled.
     *
     * @param int $tier
     * @return bool
     */
    public function canBuildTier(int $tier): bool
    {
        return $tier !== GemTierValue::TIER_ONE || $this->characterGemRollService->hasRollableAbilities();
    }

    /**
     * Build a new character Gem of the tier, or reuse an identical existing one.
     *
     * Returns null when the tier cannot roll its modifiers.
     *
     * @param int $tier
     * @return Gem|null
     */
    public function buildGem(int $tier): ?Gem
    {
        $name = $this->names[$this->randomNumberGenerator->numberBetween(0, count($this->names) - 1)];
        $rolls = $this->characterGemRollService->rollForTier($tier);

        if (empty($rolls)) {
            return null;
        }

        return $this->findIdenticalGem($name, $tier, $rolls) ?? $this->createGem($name, $tier, $rolls);
    }

    /**
     * Find an existing character Gem with the same name, tier and normalized modifier signature.
     *
     * @param string $name
     * @param int $tier
     * @param array $rolls
     * @return Gem|null
     */
    private function findIdenticalGem(string $name, int $tier, array $rolls): ?Gem
    {
        $signature = $this->rollsSignature($rolls);

        return Gem::character()
            ->where('tier', $tier)
            ->where('name', $name)
            ->with('characterModifiers.gameGemAbility')
            ->get()
            ->first(fn (Gem $candidate): bool => $this->modifiersSignature($candidate) === $signature);
    }

    /**
     * Create the character Gem and its three modifier rows.
     *
     * @param string $name
     * @param int $tier
     * @param array $rolls
     * @return Gem
     */
    private function createGem(string $name, int $tier, array $rolls): Gem
    {
        $gem = Gem::create([
            'name' => $name,
            'tier' => $tier,
            'domain' => Gem::DOMAIN_CHARACTER,
        ]);

        $gem->characterModifiers()->createMany(
            array_map(fn (CharacterGemRoll $roll): array => $roll->toModifierAttributes(), $rolls)
        );

        return $gem->load('characterModifiers.gameGemAbility');
    }

    /**
     * Build the normalized signature of a set of rolls.
     *
     * @param array $rolls
     * @return string
     */
    private function rollsSignature(array $rolls): string
    {
        return implode('|', array_map(fn (CharacterGemRoll $roll): string => $roll->signature(), $rolls));
    }

    /**
     * Build the normalized signature of an existing Gem's persisted modifier rows.
     *
     * @param Gem $gem
     * @return string
     */
    private function modifiersSignature(Gem $gem): string
    {
        return $gem->characterModifiers
            ->map(fn (CharacterGemModifier $modifier): string => $this->modifierRoll($modifier)->signature())
            ->implode('|');
    }

    /**
     * Rebuild the roll value that a persisted modifier row represents.
     *
     * @param CharacterGemModifier $modifier
     * @return CharacterGemRoll
     */
    private function modifierRoll(CharacterGemModifier $modifier): CharacterGemRoll
    {
        if (is_null($modifier->game_gem_ability_id)) {
            return CharacterGemRoll::withAmount($modifier->roll_position, $modifier->modifier_type, $modifier->amount);
        }

        return CharacterGemRoll::withAbility($modifier->roll_position, $modifier->game_gem_ability_id);
    }
}
