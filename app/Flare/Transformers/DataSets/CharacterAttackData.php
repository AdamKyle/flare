<?php

namespace App\Flare\Transformers\DataSets;

use Illuminate\Support\Facades\Cache;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;

class CharacterAttackData {

    public function attackData(Character $character, CharacterStatBuilder $characterStatBuilder, CharacterInformationBuilder $characterInformation, HolyStacks $holyStacks): array {
        $accuracySkill                = Skill::where('game_skill_id', GameSkill::where('name', 'Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $castingAccuracySkill         = Skill::where('game_skill_id', GameSkill::where('name', 'Casting Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $dodgeSkill                   = Skill::where('game_skill_id', GameSkill::where('name', 'Dodge')->first()->id)->where('character_id', $character->id)->first();
        $criticalitySkill             = Skill::where('game_skill_id', GameSkill::where('name', 'Criticality')->first()->id)->where('character_id', $character->id)->first();

        return [
            'attack'                      => $characterStatBuilder->buildTotalAttack(),
            'health'                      => $characterStatBuilder->buildHealth(),
            'ac'                          => $characterStatBuilder->buildDefence(),
            'heal_for'                    => $characterStatBuilder->buildHealing(),
            'damage_stat'                 => $character->damage_stat,
            'to_hit_stat'                 => $character->class->to_hit_stat,
            'to_hit_base'                 => $character->{$character->class->to_hit_stat},
            'voided_to_hit_base'          => $characterStatBuilder->statMod($character->class->to_hit_stat, true),
            'base_stat'                   => $characterStatBuilder->statMod($character->class->damage_stat),
            'voided_base_stat'            => $character->{$character->class->damage_stat},
            'str_modded'                  => $characterStatBuilder->statMod('str'),
            'dur_modded'                  => $characterStatBuilder->statMod('dur'),
            'dex_modded'                  => $characterStatBuilder->statMod('dex'),
            'chr_modded'                  => $characterStatBuilder->statMod('chr'),
            'int_modded'                  => $characterStatBuilder->statMod('int'),
            'agi_modded'                  => $characterStatBuilder->statMod('agi'),
            'focus_modded'                => $characterStatBuilder->statMod('focus'),
            'weapon_attack'               => $characterStatBuilder->buildDamage('weapon'),
            'voided_weapon_attack'        => $characterStatBuilder->buildDamage('weapon', true),
            'ring_damage'                 => $characterStatBuilder->buildDamage('ring'),
            'voided_ring_damage'          => $characterStatBuilder->buildDamage('ring', true),
            'spell_damage'                => $characterStatBuilder->buildDamage('spell-damage'),
            'voided_spell_damage'         => $characterStatBuilder->buildDamage('spell-damage', true),
            'healing_amount'              => $characterStatBuilder->buildHealing(),
            'voided_healing_amount'       => $characterStatBuilder->buildHealing(true),
            'devouring_light'             => $characterInformation->getDevouringLight(),
            'devouring_darkness'          => $characterInformation->getDevouringDarkness(),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'                  => $holyStacks->fetchHolyBonus($character),
            'devouring_resistance'        => $holyStacks->fetchDevouringResistanceBonus($character),
            'max_holy_stacks'             => $holyStacks->fetchTotalStacksForCharacter($character),
            'current_stacks'              => $holyStacks->fetchTotalHolyStacks($character),
            'holy_attack_bonus'           => $holyStacks->fetchAttackBonus($character),
            'holy_ac_bonus'               => $holyStacks->fetchDefenceBonus($character),
            'holy_healing_bonus'          => $holyStacks->fetchHealingBonus($character),
            'ambush_chance'               => $characterStatBuilder->buildAmbush(),
            'ambush_resistance_chance'    => $characterStatBuilder->buildAmbush('resistance'),
            'counter_chance'              => $characterStatBuilder->buildCounter(),
            'counter_resistance_chance'   => $characterStatBuilder->buildCounter('resistance'),
            'skills'                      => [
                'accuracy'         => $accuracySkill->skill_bonus,
                'casting_accuracy' => $castingAccuracySkill->skill_bonus,
                'dodge'            => $dodgeSkill->skill_bonus,
                'criticality'      => $criticalitySkill->skill_bonus,
            ],
            'devouring_light_res'         => $holyStacks->fetchDevouringResistanceBonus($character),
            'devouring_darkness_res'      => $holyStacks->fetchDevouringResistanceBonus($character),
            'spell_evasion'               => $characterInformation->getTotalDeduction('spell_evasion'),
            'affix_damage_reduction'      => $characterInformation->getTotalDeduction('affix_damage_reduction'),
            'healing_reduction'           => $characterInformation->getTotalDeduction('healing_reduction'),
            'skill_reduction'             => $characterInformation->getBestSkillReduction(),
            'resistance_reduction'        => $characterInformation->getBestResistanceReduction(),
            'stat_affixes'                => [
                'cant_be_resisted'   => $characterInformation->canAffixesBeResisted(),
                'all_stat_reduction' => $characterInformation->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterInformation->findSuffixStatReductionAffixes(),
            ],
            'attack_types'           => $this->fetchAttackTypes($character),
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
