<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Core\Items\Values\ItemType;

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
            'health' => $this->roundStat($characterStatBuilder->buildHealth()),
            'voided_health' => $this->roundStat($characterStatBuilder->buildhealth(true)),
            'ac' => $this->roundStat($characterStatBuilder->buildDefence()),
            'voided_ac' => $this->roundStat($characterStatBuilder->buildDefence(true)),
            'str_modded' => $this->roundStat($characterStatBuilder->statMod('str')),
            'dur_modded' => $this->roundStat($characterStatBuilder->statMod('dur')),
            'dex_modded' => $this->roundStat($characterStatBuilder->statMod('dex')),
            'chr_modded' => $this->roundStat($characterStatBuilder->statMod('chr')),
            'int_modded' => $this->roundStat($characterStatBuilder->statMod('int')),
            'agi_modded' => $this->roundStat($characterStatBuilder->statMod('agi')),
            'focus_modded' => $this->roundStat($characterStatBuilder->statMod('focus')),
            'weapon_attack' => $this->roundStat($characterStatBuilder->buildDamage(ItemType::validWeapons())),
            'voided_weapon_attack' => $this->roundStat($characterStatBuilder->buildDamage(ItemType::validWeapons(), true)),
            'ring_damage' => $this->roundStat($characterStatBuilder->buildDamage('ring')),
            'spell_damage' => $this->roundStat($characterStatBuilder->buildDamage('spell-damage')),
            'voided_spell_damage' => $this->roundStat($characterStatBuilder->buildDamage('spell-damage', true)),
            'healing_amount' => $this->roundStat($characterStatBuilder->buildHealing()),
            'voided_healing_amount' => $this->roundStat($characterStatBuilder->buildHealing(true)),
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

    /**
     * Round a Character stat or combat value to the nearest whole number.
     *
     * @param float|int $value
     * @return int
     */
    private function roundStat(float|int $value): int
    {
        return round($value);
    }
}
