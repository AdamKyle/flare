<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\Character\AttackDetails\CharacterHealthInformation;
use App\Flare\Builders\Character\AttackDetails\CharacterTrinketsInformation;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\GameSkill;
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
            'skills'                      => $character->skills()->whereIn('game_skill_id', GameSkill::whereIn('name', ['Accuracy', 'Dodge', 'Casting Accuracy', 'Criticality'])->pluck('id')->toArray())->get(),
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
