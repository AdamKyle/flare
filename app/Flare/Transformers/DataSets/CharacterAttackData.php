<?php

namespace App\Flare\Transformers\DataSets;

use Illuminate\Support\Facades\Cache;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\Character;
use App\Flare\Values\ClassAttackValue;

class CharacterAttackData {

    private bool $includeReductions = false;

    public function setIncludeReductions($includdeReductions = false) {
        $this->includeReductions = $includdeReductions;
    }

    public function attackData(Character $character, CharacterStatBuilder $characterStatBuilder): array {

        return [
            'level'                       => number_format($character->level),
            'attack'                      => $characterStatBuilder->buildTotalAttack(),
            'health'                      => $characterStatBuilder->buildHealth($this->includeReductions),
            'ac'                          => $characterStatBuilder->buildDefence($this->includeReductions),
            'heal_for'                    => $characterStatBuilder->buildHealing($this->includeReductions),
            'to_hit_stat'                 => $character->class->to_hit_stat,
            'base_stat'                   => $characterStatBuilder->statMod($character->class->damage_stat, $this->includeReductions),
            'str_modded'                  => $characterStatBuilder->statMod('str', $this->includeReductions),
            'dur_modded'                  => $characterStatBuilder->statMod('dur', $this->includeReductions),
            'dex_modded'                  => $characterStatBuilder->statMod('dex', $this->includeReductions),
            'chr_modded'                  => $characterStatBuilder->statMod('chr', $this->includeReductions),
            'int_modded'                  => $characterStatBuilder->statMod('int', $this->includeReductions),
            'agi_modded'                  => $characterStatBuilder->statMod('agi', $this->includeReductions),
            'focus_modded'                => $characterStatBuilder->statMod('focus', $this->includeReductions),
            'devouring_light'             => $characterStatBuilder->buildDevouring('devouring_light'),
            'devouring_darkness'          => $characterStatBuilder->buildDevouring('devouring_darkness'),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'devouring_resistance'        => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'ambush_chance'               => $characterStatBuilder->buildAmbush(),
            'ambush_resistance_chance'    => $characterStatBuilder->buildAmbush('resistance'),
            'counter_chance'              => $characterStatBuilder->buildCounter(),
            'counter_resistance_chance'   => $characterStatBuilder->buildCounter('resistance'),
            'devouring_light_res'         => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'devouring_darkness_res'      => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'spell_evasion'               => $characterStatBuilder->reductionInfo()->getRingReduction('spell_evasion'),
            'affix_damage_reduction'      => $characterStatBuilder->reductionInfo()->getRingReduction('affix_damage_reduction'),
            'healing_reduction'           => $characterStatBuilder->reductionInfo()->getRingReduction('healing_reduction'),
            'skill_reduction'             => $characterStatBuilder->reductionInfo()->getAffixReduction('skill_reduction'),
            'resistance_reduction'        => $characterStatBuilder->reductionInfo()->getAffixReduction('resistance_reduction'),
        ];
    }

    public function fetchAttackTypes(Character $character): array {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['attack_types'];
    }
}
