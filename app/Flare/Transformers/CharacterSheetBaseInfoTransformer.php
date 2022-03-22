<?php

namespace App\Flare\Transformers;


use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Values\View\ClassBonusInformation;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;
use App\Flare\Builders\Character\AttackDetails\CharacterAffixInformation;
use App\Flare\Builders\Character\AttackDetails\CharacterHealthInformation;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;
use App\Game\Skills\Values\SkillTypeValue;

class CharacterSheetBaseInfoTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        $characterInformation       = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        $characterHealthInformation = resolve(CharacterHealthInformation::class)->setCharacter($character);
        $characterAffixInformation  = resolve(CharacterAffixInformation::class)->setCharacter($character);
        $holyStacks                 = resolve(HolyStacks::class);

        return [
            'id'                => $character->id,
            'name'              => $character->name,
            'attack'            => number_format($this->fetchStats($character,'attack')),
            'health'            => number_format($this->fetchStats($character,'health')),
            'ac'                => number_format($this->fetchStats($character,'ac')),
            'heal_for'          => number_format($characterHealthInformation->buildHealFor()),
            'damage_stat'       => $character->damage_stat,
            'to_hit_stat'       => $character->class->to_hit_stat,
            'to_hit_base'       => $this->getToHitBase($character, $characterInformation),
            'voided_to_hit_base'=> $this->getToHitBase($character, $characterInformation, true),
            'base_stat'         => $characterInformation->statMod($character->class->damage_stat),
            'voided_base_stat'  => $character->{$character->class->damage_stat},
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'inventory_max'     => $character->inventory_max,
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => (int) $character->xp,
            'xp_next'           => (int) $character->xp_next,
            'str'               => number_format($character->str),
            'dur'               => number_format($character->dur),
            'dex'               => number_format($character->dex),
            'chr'               => number_format($character->chr),
            'int'               => number_format($character->int),
            'agi'               => number_format($character->agi),
            'focus'             => number_format($character->focus),
            'str_modded'        => number_format($this->fetchStats($character, 'str_modded')),
            'dur_modded'        => number_format($this->fetchStats($character, 'dur_modded')),
            'dex_modded'        => number_format($this->fetchStats($character, 'dex_modded')),
            'chr_modded'        => number_format($this->fetchStats($character, 'chr_modded')),
            'int_modded'        => number_format($this->fetchStats($character, 'int_modded')),
            'agi_modded'        => number_format($this->fetchStats($character, 'agi_modded')),
            'focus_modded'      => number_format($this->fetchStats($character, 'focus_modded')),
            'spell_evasion'     => $characterInformation->getTotalDeduction('spell_evasion'),
            'artifact_anull'    => $characterInformation->getTotalDeduction('artifact_annulment'),
            'healing_reduction' => $characterInformation->getTotalDeduction('healing_reduction'),
            'affix_damage_red'  => $characterInformation->getTotalDeduction('affix_damage_reduction'),
            'res_chance'        => $characterHealthInformation->fetchResurrectionChance(),
            'weapon_attack'     => number_format($characterInformation->getTotalWeaponDamage()),
            'rings_attack'      => number_format($characterInformation->getTotalRingDamage()),
            'spell_damage'      => number_format($characterInformation->getTotalSpellDamage()),
            'artifact_damage'   => number_format($characterInformation->getTotalArtifactDamage()),
            'class_bonus'       => (new ClassBonusInformation())->buildClassBonusDetails($character),
            'devouring_light'   => $characterInformation->getDevouringLight(),
            'devouring_darkness'  => $characterInformation->getDevouringDarkness(),
            'attack_stats'        => Cache::get('character-attack-data-' . $character->id)['attack_types'],
            'extra_action_chance' => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'           => $holyStacks->fetchHolyBonus($character),
            'devouring_resistance' => $holyStacks->fetchDevouringResistanceBonus($character),
            'max_holy_stacks'      => $holyStacks->fetchTotalStacksForCharacter($character),
            'current_stacks'       => $holyStacks->fetchTotalHolyStacks($character),
            'holy_attack_bonus'    => $holyStacks->fetchAttackBonus($character),
            'holy_ac_bonus'        => $holyStacks->fetchDefenceBonus($character),
            'holy_healing_bonus'   => $holyStacks->fetchHealingBonus($character),
            'gold'                 => number_format($character->gold),
            'gold_dust'            => number_format($character->gold_dust),
            'shards'               => number_format($character->shards),
            'copper_coins'         => number_format($character->copper_coins),
            'is_dead'              => $character->is_dead,
            'can_adventure'        => $character->can_adventure,
            'stat_affixes'        => [
                'cant_be_resisted'   => $characterInformation->canAffixesBeResisted(),
                'all_stat_reduction' => $characterAffixInformation->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterAffixInformation->findSuffixStatReductionAffixes(),
            ],
            'is_alchemy_locked'      => $this->isAlchemyLocked($character),
        ];
    }
}
