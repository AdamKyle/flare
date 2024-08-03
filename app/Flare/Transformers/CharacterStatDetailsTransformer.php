<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;

class CharacterStatDetailsTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions): void
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {
        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);

        return [
            'str' => $character->str,
            'dur' => $character->dur,
            'dex' => $character->dex,
            'chr' => $character->chr,
            'int' => $character->int,
            'agi' => $character->agi,
            'focus' => $character->focus,
            'health' => $characterStatBuilder->buildHealth(),
            'voided_health' => $characterStatBuilder->buildhealth(true),
            'ac' => $characterStatBuilder->buildDefence(),
            'voided_ac' => $characterStatBuilder->buildDefence(true),
            'str_modded' => $characterStatBuilder->statMod('str'),
            'dur_modded' => $characterStatBuilder->statMod('dur'),
            'dex_modded' => $characterStatBuilder->statMod('dex'),
            'chr_modded' => $characterStatBuilder->statMod('chr'),
            'int_modded' => $characterStatBuilder->statMod('int'),
            'agi_modded' => $characterStatBuilder->statMod('agi'),
            'focus_modded' => $characterStatBuilder->statMod('focus'),
            'weapon_attack' => $characterStatBuilder->buildDamage('weapon'),
            'voided_weapon_attack' => $characterStatBuilder->buildDamage('weapon', true),
            'ring_damage' => $characterStatBuilder->buildDamage('ring'),
            'spell_damage' => $characterStatBuilder->buildDamage('spell-damage'),
            'voided_spell_damage' => $characterStatBuilder->buildDamage('spell-damage', true),
            'healing_amount' => $characterStatBuilder->buildHealing(),
            'voided_healing_amount' => $characterStatBuilder->buildHealing(true),
            'devouring_light' => $characterStatBuilder->buildDevouring('devouring_light'),
            'devouring_darkness' => $characterStatBuilder->buildDevouring('devouring_darkness'),
            'holy_bonus' => $characterStatBuilder->holyInfo()->fetchHolyBonus(),
            'max_holy_stacks' => $characterStatBuilder->holyInfo()->fetchTotalStacksForCharacter(),
            'current_stacks' => $characterStatBuilder->holyInfo()->getTotalAppliedStacks(),
            'stat_increase_bonus' => $characterStatBuilder->holyInfo()->fetchStatIncrease(),
            'holy_attack_bonus' => $characterStatBuilder->holyInfo()->fetchAttackBonus(),
            'holy_ac_bonus' => $characterStatBuilder->holyInfo()->fetchDefenceBonus(),
            'holy_healing_bonus' => $characterStatBuilder->holyInfo()->fetchHealingBonus(),
            'ambush_chance' => $characterStatBuilder->buildAmbush(),
            'ambush_resistance_chance' => $characterStatBuilder->buildAmbush('resistance'),
            'counter_chance' => $characterStatBuilder->buildCounter(),
            'counter_resistance_chance' => $characterStatBuilder->buildCounter('resistance'),
            'devouring_light_res' => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'devouring_darkness_res' => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
        ];
    }
}
