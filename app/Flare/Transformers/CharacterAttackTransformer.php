<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\Character\AttackDetails\CharacterHealthInformation;
use App\Flare\Builders\Character\AttackDetails\CharacterTrinketsInformation;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends BaseTransformer {

    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $characterInformation         = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        $characterHealthInformation   = resolve(CharacterHealthInformation::class)->setCharacter($character);
        $holyStacks                   = resolve(HolyStacks::class);
        $characterTrinketsInformation = resolve(CharacterTrinketsInformation::class);

        $accuracySkill                = Skill::where('game_skill_id', GameSkill::where('name', 'Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $castingAccuracySkill         = Skill::where('game_skill_id', GameSkill::where('name', 'Casting Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $dodgeSkill                   = Skill::where('game_skill_id', GameSkill::where('name', 'Dodge')->first()->id)->where('character_id', $character->id)->first();
        $criticalitySkill             = Skill::where('game_skill_id', GameSkill::where('name', 'Criticality')->first()->id)->where('character_id', $character->id)->first();

        return [
            'inventory_max'               => $character->inventory_max,
            'inventory_count'             => $character->getInventoryCount(),
            'attack'                      => $characterInformation->buildTotalAttack(),
            'health'                      => $characterInformation->buildHealth(),
            'ac'                          => $characterInformation->buildDefence(),
            'heal_for'                    => $characterHealthInformation->buildHealFor(),
            'damage_stat'                 => $character->damage_stat,
            'to_hit_stat'                 => $character->class->to_hit_stat,
            'to_hit_base'                 => $character->{$character->class->to_hit_stat}, //$this->getToHitBase($character, $characterInformation),
            'voided_to_hit_base'          => $this->getToHitBase($character, $characterInformation, true),
            'base_stat'                   => $characterInformation->statMod($character->class->damage_stat),
            'voided_base_stat'            => $character->{$character->class->damage_stat},
            'str_modded'                  => round($characterInformation->statMod('str')),
            'dur_modded'                  => round($characterInformation->statMod('dur')),
            'dex_modded'                  => round($characterInformation->statMod('dex')),
            'chr_modded'                  => round($characterInformation->statMod('chr')),
            'int_modded'                  => round($characterInformation->statMod('int')),
            'agi_modded'                  => round($characterInformation->statMod('agi')),
            'focus_modded'                => round($characterInformation->statMod('focus')),
            'weapon_attack'               => round($characterInformation->getTotalWeaponDamage()),
            'voided_weapon_attack'        => round($characterInformation->getTotalWeaponDamage(false)),
            'ring_damage'                 => round($characterInformation->getTotalRingDamage()),
            'voided_ring_damage'          => round($characterInformation->getTotalRingDamage(true)),
            'spell_damage'                => round($characterInformation->getTotalSpellDamage()),
            'voided_spell_damage'         => round($characterInformation->getTotalSpellDamage(true)),
            'healing_amount'              => round($characterInformation->buildHealFor()),
            'voided_healing_amount'       => round($characterInformation->buildHealFor(true)),
            'devouring_light'             => round($characterInformation->getDevouringLight()),
            'devouring_darkness'          => round($characterInformation->getDevouringDarkness()),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'                  => $holyStacks->fetchHolyBonus($character),
            'devouring_resistance'        => $holyStacks->fetchDevouringResistanceBonus($character),
            'max_holy_stacks'             => $holyStacks->fetchTotalStacksForCharacter($character),
            'current_stacks'              => $holyStacks->fetchTotalHolyStacks($character),
            'holy_attack_bonus'           => $holyStacks->fetchAttackBonus($character),
            'holy_ac_bonus'               => $holyStacks->fetchDefenceBonus($character),
            'holy_healing_bonus'          => $holyStacks->fetchHealingBonus($character),
            'ambush_chance'               => $characterTrinketsInformation->getAmbushChance($character),
            'ambush_resistance_chance'    => $characterTrinketsInformation->getAmbushResistanceChance($character),
            'counter_chance'              => $characterTrinketsInformation->getCounterChance($character),
            'counter_resistance_chance'   => $characterTrinketsInformation->getCounterResistanceChance($character),
            'skills'                      => [
                'accuracy'         => $accuracySkill->skill_bonus,
                'casting_accuracy' => $castingAccuracySkill->skill_bonus,
                'dodge'            => $dodgeSkill->skill_bonus,
                'criticality'      => $criticalitySkill->skill_bonus,
            ],
            'devouring_light_res'         => $holyStacks->fetchDevouringResistanceBonus($character),
            'devouring_darkness_res'      => $holyStacks->fetchDevouringResistanceBonus($character),
            'ambush_resistance'           => $characterTrinketsInformation->getAmbushResistanceChance($character),
            'counter_resistance'          => $characterTrinketsInformation->getCounterResistanceChance($character),
            'voided_weapon_attack'        => $characterInformation->getTotalWeaponDamage(false),
            'artifact_annulment'          => $characterInformation->getTotalDeduction('artifact_annulment'),
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
        ];
    }
}
