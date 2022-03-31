<?php

namespace App\Flare\Transformers;



use App\Flare\Models\GameClass;
use Cache;
use App\Game\Core\Values\View\ClassBonusInformation;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Builders\Character\AttackDetails\CharacterAffixInformation;
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
        $characterAffixInformation    = resolve(CharacterAffixInformation::class)->setCharacter($character);
        $holyStacks                   = resolve(HolyStacks::class);
        $characterTrinketsInformation = resolve(CharacterTrinketsInformation::class);
        $gameClass                    = GameClass::find($character->game_class_id);

        return [
            'id'                        => $character->id,
            'name'                      => $character->name,
            'class'                     => $gameClass->name,
            'attack'                    => $this->fetchStats($character,'attack'),
            'health'                    => $this->fetchStats($character,'health'),
            'ac'                        => $this->fetchStats($character,'ac'),
            'heal_for'                  => $characterHealthInformation->buildHealFor(),
            'damage_stat'               => $character->damage_stat,
            'to_hit_stat'               => $character->class->to_hit_stat,
            'to_hit_base'               => $this->getToHitBase($character, $characterInformation),
            'voided_to_hit_base'        => $this->getToHitBase($character, $characterInformation, true),
            'base_stat'                 => $characterInformation->statMod($character->class->damage_stat),
            'voided_base_stat'          => $character->{$character->class->damage_stat},
            'race'                      => $character->race->name,
            'class'                     => $character->class->name,
            'inventory_max'             => $character->inventory_max,
            'level'                     => number_format($character->level),
            'max_level'                 => number_format($this->getMaxLevel($character)),
            'xp'                        => (int) $character->xp,
            'xp_next'                   => (int) $character->xp_next,
            'str'                       => $character->str,
            'dur'                       => $character->dur,
            'dex'                       => $character->dex,
            'chr'                       => $character->chr,
            'int'                       => $character->int,
            'agi'                       => $character->agi,
            'focus'                     => $character->focus,
            'str_modded'                => $this->fetchStats($character, 'str_modded'),
            'dur_modded'                => $this->fetchStats($character, 'dur_modded'),
            'dex_modded'                => $this->fetchStats($character, 'dex_modded'),
            'chr_modded'                => $this->fetchStats($character, 'chr_modded'),
            'int_modded'                => $this->fetchStats($character, 'int_modded'),
            'agi_modded'                => $this->fetchStats($character, 'agi_modded'),
            'focus_modded'              => $this->fetchStats($character, 'focus_modded'),
            'spell_evasion'             => $characterInformation->getTotalDeduction('spell_evasion'),
            'artifact_anull'            => $characterInformation->getTotalDeduction('artifact_annulment'),
            'healing_reduction'         => $characterInformation->getTotalDeduction('healing_reduction'),
            'affix_damage_red'          => $characterInformation->getTotalDeduction('affix_damage_reduction'),
            'res_chance'                => $characterHealthInformation->fetchResurrectionChance(),
            'weapon_attack'             => $characterInformation->getTotalWeaponDamage(),
            'rings_attack'              => number_format($characterInformation->getTotalRingDamage()),
            'spell_damage'              => number_format($characterInformation->getTotalSpellDamage()),
            'artifact_damage'           => number_format($characterInformation->getTotalArtifactDamage()),
            'class_bonus'               => (new ClassBonusInformation())->buildClassBonusDetails($character),
            'devouring_light'           => $characterInformation->getDevouringLight(),
            'devouring_darkness'        => $characterInformation->getDevouringDarkness(),
            'attack_types'              => $this->fetchAttackTypes($character),
            'extra_action_chance'       => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'                => $holyStacks->fetchHolyBonus($character),
            'devouring_resistance'      => $holyStacks->fetchDevouringResistanceBonus($character),
            'max_holy_stacks'           => $holyStacks->fetchTotalStacksForCharacter($character),
            'current_stacks'            => $holyStacks->fetchTotalHolyStacks($character),
            'holy_attack_bonus'         => $holyStacks->fetchAttackBonus($character),
            'holy_ac_bonus'             => $holyStacks->fetchDefenceBonus($character),
            'holy_healing_bonus'        => $holyStacks->fetchHealingBonus($character),
            'gold'                      => number_format($character->gold),
            'gold_dust'                 => number_format($character->gold_dust),
            'shards'                    => number_format($character->shards),
            'copper_coins'              => number_format($character->copper_coins),
            'is_dead'                   => $character->is_dead,
            'can_adventure'             => $character->can_adventure,
            'ambush_chance'             => $characterTrinketsInformation->getAmbushChance($character),
            'ambush_resistance_chance'  => $characterTrinketsInformation->getAmbushResistanceChance($character),
            'counter_chance'            => $characterTrinketsInformation->getCounterChance($character),
            'counter_resistance_chance' => $characterTrinketsInformation->getCounterResistanceChance($character),
            'stat_affixes'        => [
                'cant_be_resisted'   => $characterInformation->canAffixesBeResisted(),
                'all_stat_reduction' => $characterAffixInformation->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterAffixInformation->findSuffixStatReductionAffixes(),
            ],
            'skills'                      => $this->fetchSkills($character),
            'devouring_light_res'         => $holyStacks->fetchDevouringResistanceBonus($character),
            'devouring_darkness_res'      => $holyStacks->fetchDevouringResistanceBonus($character),
            'skill_reduction'             => $this->fetchStats($character, 'skill_reduction'),
            'resistance_reduction'        => $this->fetchStats($character, 'resistance_reduction'),
            'disable_pop_overs'           => $character->user->disable_attack_type_popover,
            'is_attack_automation_locked' => $character->is_attack_automation_locked,
            'can_attack_again_at'         => $character->can_attack_again_at,
            'ambush_resistance'           => $characterTrinketsInformation->getAmbushResistanceChance($character),
            'counter_resistance'          => $characterTrinketsInformation->getCounterResistanceChance($character),
            'voided_weapon_attack'        => $characterInformation->getTotalWeaponDamage(false),
        ];
    }
}
