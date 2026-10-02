<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\GameGemAbility;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Contracts\CharacterGemRollFacts;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\CharacterGemRoll;
use App\Game\Gems\Values\GemAbilitySnapshot;
use App\Game\Gems\Values\GemModifierRange;
use App\Game\Gems\Values\GemTierValue;

class CharacterGemRollService implements CharacterGemRollFacts
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
    ) {}

    /**
     * Determine whether at least one enabled Gem Ability exists for Tier 1 crafting.
     *
     * @return bool
     */
    public function hasRollableAbilities(): bool
    {
        return GameGemAbility::where('enabled', true)->exists();
    }

    /**
     * Roll the three distinct modifiers for a new character Gem of the given tier.
     *
     * Returns an empty array when a Tier 1 Gem cannot roll because no enabled Gem Ability exists.
     *
     * @param int $tier
     * @return array
     */
    public function rollForTier(int $tier): array
    {
        if ($tier === GemTierValue::TIER_ONE) {
            return $this->rollTierOne();
        }

        if ($tier === GemTierValue::TIER_FOUR) {
            return $this->rollTierFour();
        }

        return $this->buildRolls($tier, $this->pickDistinct(CharacterGemModifierType::forTier($tier), 3));
    }

    /**
     * Return the canonical non-ability modifier roll ranges for the Gem tier.
     *
     * @param int $tier
     * @return array
     */
    public function possibleModifierRanges(int $tier): array
    {
        return collect(CharacterGemModifierType::forTier($tier))
            ->reject(fn (CharacterGemModifierType $type): bool => $type === CharacterGemModifierType::GEM_ABILITY)
            ->map(fn (CharacterGemModifierType $type): GemModifierRange => $this->amountRange($tier, $type))
            ->values()
            ->all();
    }

    /**
     * Return the enabled Gem Abilities a Tier 1 Gem can roll; other tiers roll no ability.
     *
     * @param int $tier
     * @return array
     */
    public function possibleAbilities(int $tier): array
    {
        if ($tier !== GemTierValue::TIER_ONE) {
            return [];
        }

        return GameGemAbility::where('enabled', true)
            ->orderBy('name')
            ->get()
            ->map(fn (GameGemAbility $ability): GemAbilitySnapshot => GemAbilitySnapshot::fromGameGemAbility($ability))
            ->values()
            ->all();
    }

    /**
     * Roll one enabled Gem Ability followed by two distinct raw stats.
     *
     * @return array
     */
    private function rollTierOne(): array
    {
        $abilityIds = GameGemAbility::where('enabled', true)->orderBy('id')->pluck('id')->all();

        if (empty($abilityIds)) {
            return [];
        }

        $abilityId = $abilityIds[$this->randomNumberGenerator->numberBetween(0, count($abilityIds) - 1)];

        return [
            CharacterGemRoll::withAbility(1, $abilityId),
            ...$this->buildRolls(GemTierValue::TIER_ONE, $this->pickDistinct(CharacterGemModifierType::rawStats(), 2), 2),
        ];
    }

    /**
     * Roll one atonement and two valid additional Tier 4 modifiers.
     *
     * @return array
     */
    private function rollTierFour(): array
    {
        $atonement = $this->pickDistinct(CharacterGemModifierType::atonements(), 1)[0];
        $additionalPool = [
            $this->matchingPenetration($atonement),
            ...CharacterGemModifierType::currencyGains(),
            CharacterGemModifierType::CHARACTER_XP_GAIN,
        ];

        if ($this->chanceCalculator->passesPercentage(1)) {
            $additionalModifiers = [
                CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN,
                ...$this->pickDistinct($additionalPool, 1),
            ];
        } else {
            $additionalModifiers = $this->pickDistinct($additionalPool, 2);
        }

        return $this->buildRolls(GemTierValue::TIER_FOUR, [
            $atonement,
            ...$additionalModifiers,
        ]);
    }

    /**
     * Return the penetration type matching the selected atonement.
     *
     * @param CharacterGemModifierType $atonement
     * @return CharacterGemModifierType
     */
    private function matchingPenetration(CharacterGemModifierType $atonement): CharacterGemModifierType
    {
        return match ($atonement) {
            CharacterGemModifierType::FIRE_ATONEMENT => CharacterGemModifierType::FIRE_PENETRATION,
            CharacterGemModifierType::WATER_ATONEMENT => CharacterGemModifierType::WATER_PENETRATION,
            CharacterGemModifierType::ICE_ATONEMENT => CharacterGemModifierType::ICE_PENETRATION,
        };
    }

    /**
     * Randomly choose the requested number of distinct modifier types from the pool.
     *
     * @param array $pool
     * @param int $count
     * @return array
     */
    private function pickDistinct(array $pool, int $count): array
    {
        $remaining = array_values($pool);
        $picked = [];

        while (count($picked) < $count) {
            $index = $this->randomNumberGenerator->numberBetween(0, count($remaining) - 1);

            $picked[] = $remaining[$index];

            array_splice($remaining, $index, 1);
        }

        return $picked;
    }

    /**
     * Build positioned rolls for the chosen modifier types, rolling each amount for the tier.
     *
     * @param int $tier
     * @param array $modifierTypes
     * @param int $firstPosition
     * @return array
     */
    private function buildRolls(int $tier, array $modifierTypes, int $firstPosition = 1): array
    {
        return array_map(
            fn (CharacterGemModifierType $modifierType, int $offset): CharacterGemRoll => CharacterGemRoll::withAmount(
                $firstPosition + $offset,
                $modifierType,
                $this->rollAmount($tier, $modifierType),
            ),
            $modifierTypes,
            array_keys($modifierTypes),
        );
    }

    /**
     * Roll the amount of a modifier type within its tier range.
     *
     * @param int $tier
     * @param CharacterGemModifierType $modifierType
     * @return float
     */
    private function rollAmount(int $tier, CharacterGemModifierType $modifierType): float
    {
        $range = $this->amountRange($tier, $modifierType);
        $amount = $this->randomNumberGenerator->numberBetween($range->minimum, $range->maximum);

        return $range->percentage ? $amount / 100 : $amount;
    }

    /**
     * Return the canonical whole-number roll bounds and whether they represent percentages.
     *
     * @param int $tier
     * @param CharacterGemModifierType $modifierType
     * @return GemModifierRange
     */
    private function amountRange(int $tier, CharacterGemModifierType $modifierType): GemModifierRange
    {
        if ($modifierType->isRawStat()) {
            return new GemModifierRange(
                $modifierType,
                $tier === GemTierValue::TIER_ONE ? 5 : 25,
                $tier === GemTierValue::TIER_ONE ? 25 : 100,
                false,
            );
        }

        [$minimum, $maximum] = match ($modifierType) {
            CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN => [1, 1],
            CharacterGemModifierType::BASE_DAMAGE_MOD,
            CharacterGemModifierType::BASE_AC_MOD,
            CharacterGemModifierType::BASE_HEALING_MOD,
            CharacterGemModifierType::BASE_SPELL_DAMAGE_MOD => [1, 5],
            CharacterGemModifierType::CLASS_MASTERY_EFFECT,
            CharacterGemModifierType::WEAPON_MASTERY_EFFECT => [2, 8],
            CharacterGemModifierType::FIRE_PENETRATION,
            CharacterGemModifierType::WATER_PENETRATION,
            CharacterGemModifierType::ICE_PENETRATION => [3, 10],
            default => [5, 15],
        };

        return new GemModifierRange(
            $modifierType,
            $minimum,
            $maximum,
            $modifierType !== CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN,
        );
    }
}
