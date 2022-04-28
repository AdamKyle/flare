<?php

namespace App\Flare\Transformers;


use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use Cache;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Builders\Character\AttackDetails\CharacterHealthInformation;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\Character\AttackDetails\CharacterTrinketsInformation;
use App\Flare\Values\ClassAttackValue;

class CharacterSheetBaseInfoTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        $characterInformation         = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        $characterHealthInformation   = resolve(CharacterHealthInformation::class)->setCharacter($character);
        $holyStacks                   = resolve(HolyStacks::class);
        $characterTrinketsInformation = resolve(CharacterTrinketsInformation::class);
        $gameClass                    = GameClass::find($character->game_class_id);


        $accuracySkill                = Skill::where('game_skill_id', GameSkill::where('name', 'Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $castingAccuracySkill         = Skill::where('game_skill_id', GameSkill::where('name', 'Casting Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $dodgeSkill                   = Skill::where('game_skill_id', GameSkill::where('name', 'Dodge')->first()->id)->where('character_id', $character->id)->first();
        $criticalitySkill             = Skill::where('game_skill_id', GameSkill::where('name', 'Criticality')->first()->id)->where('character_id', $character->id)->first();


        return [
            'id'                          => $character->id,
            'user_id'                     => $character->user_id,
            'name'                        => $character->name,
            'class'                       => $gameClass->name,
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
            'race'                        => $character->race->name,
            'inventory_max'               => $character->inventory_max,
            'inventory_count'             => $character->getInventoryCount(),
            'level'                       => number_format($character->level),
            'max_level'                   => number_format($this->getMaxLevel($character)),
            'xp'                          => (int) $character->xp,
            'xp_next'                     => (int) $character->xp_next,
            'str'                         => $character->str,
            'dur'                         => $character->dur,
            'dex'                         => $character->dex,
            'chr'                         => $character->chr,
            'int'                         => $character->int,
            'agi'                         => $character->agi,
            'focus'                       => $character->focus,
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
            'attack_types'                => $this->fetchAttackTypes($character),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'                  => $holyStacks->fetchHolyBonus($character),
            'devouring_resistance'        => $holyStacks->fetchDevouringResistanceBonus($character),
            'max_holy_stacks'             => $holyStacks->fetchTotalStacksForCharacter($character),
            'current_stacks'              => $holyStacks->fetchTotalHolyStacks($character),
            'holy_attack_bonus'           => $holyStacks->fetchAttackBonus($character),
            'holy_ac_bonus'               => $holyStacks->fetchDefenceBonus($character),
            'holy_healing_bonus'          => $holyStacks->fetchHealingBonus($character),
            'gold'                        => number_format($character->gold),
            'gold_dust'                   => number_format($character->gold_dust),
            'shards'                      => number_format($character->shards),
            'copper_coins'                => number_format($character->copper_coins),
            'is_dead'                     => $character->is_dead,
            'can_craft'                   => $character->can_craft,
            'can_attack'                  => $character->can_attack,
            'can_adventure'               => $character->can_adventure,
            'can_attack_again_at'         => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft_again_at'          => now()->diffInSeconds($character->can_craft_again_at),
            'can_adventure_again_at'      => now()->diffInSeconds($character->can_adventure_again_at),
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
            'is_attack_automation_locked' => $character->is_attack_automation_locked,
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
