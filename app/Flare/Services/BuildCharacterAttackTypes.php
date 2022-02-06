<?php

namespace App\Flare\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;
use Cache;
use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Models\Character;

class BuildCharacterAttackTypes {

    use SkillsTransformerTrait;

    private $characterAttackBuilder;

    public function __construct(CharacterAttackBuilder $characterAttackBuilder) {
        $this->characterAttackBuilder = $characterAttackBuilder;
    }

    public function buildCache(Character $character): array {

        $characterAttack = $this->characterAttackBuilder->setCharacter($character->refresh());

        Cache::put('character-attack-data-' . $character->id, [
            'attack_types' => [
                'attack'                 => $characterAttack->buildAttack(),
                'voided_attack'          => $characterAttack->buildAttack(true),
                'cast'                   => $characterAttack->buildCastAttack(),
                'voided_cast'            => $characterAttack->buildCastAttack(true),
                'cast_and_attack'        => $characterAttack->buildCastAndAttack(),
                'voided_cast_and_attack' => $characterAttack->buildCastAndAttack(true),
                'attack_and_cast'        => $characterAttack->buildAttackAndCast(),
                'voided_attack_and_cast' => $characterAttack->buildAttackAndCast(true),
                'defend'                 => $characterAttack->buildDefend(),
                'voided_defend'          => $characterAttack->buildDefend(true),
            ],
            'character_data' => [
                'attack'                 => $characterAttack->getInformationBuilder()->buildTotalAttack(),
                'health'                 => $characterAttack->getInformationBuilder()->buildHealth(),
                'ac'                     => $characterAttack->getInformationBuilder()->buildDefence(),
                'str_modded'             => round($characterAttack->getInformationBuilder()->statMod('str')),
                'dur_modded'             => round($characterAttack->getInformationBuilder()->statMod('dur')),
                'dex_modded'             => round($characterAttack->getInformationBuilder()->statMod('dex')),
                'chr_modded'             => round($characterAttack->getInformationBuilder()->statMod('chr')),
                'int_modded'             => round($characterAttack->getInformationBuilder()->statMod('int')),
                'agi_modded'             => round($characterAttack->getInformationBuilder()->statMod('agi')),
                'focus_modded'           => round($characterAttack->getInformationBuilder()->statMod('focus')),
                'to_hit_base'            => $this->getToHitBase($character, $characterAttack->getInformationBuilder()),
                'voided_to_hit_base'     => $this->getToHitBase($character, $characterAttack->getInformationBuilder(), true),
                'artifact_annulment'     => $characterAttack->getInformationBuilder()->getTotalDeduction('artifact_annulment'),
                'spell_evasion'          => $characterAttack->getInformationBuilder()->getTotalDeduction('spell_evasion'),
                'affix_damage_reduction' => $characterAttack->getInformationBuilder()->getTotalDeduction('affix_damage_reduction'),
                'healing_reduction'      => $characterAttack->getInformationBuilder()->getTotalDeduction('healing_reduction'),
                'devouring_light'        => $characterAttack->getInformationBuilder()->getDevouringLight(),
                'devouring_darkness'     => $characterAttack->getInformationBuilder()->getDevouringDarkness(),
                'skill_reduction'        => $characterAttack->getInformationBuilder()->getBestSkillReduction(),
                'resistance_reduction'   => $characterAttack->getInformationBuilder()->getBestResistanceReduction(),
            ],
            'stat_affixes' => [
                'cant_be_resisted'   => $characterAttack->getInformationBuilder()->canAffixesBeResisted(),
                'all_stat_reduction' => $characterAttack->getInformationBuilder()->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterAttack->getInformationBuilder()->findSuffixStatReductionAffixes(),
            ],
            'skills' => $this->fetchSkills($character->skills)
        ]);

        return Cache::get('character-attack-data-' . $character->id);
    }

    public function updateSkillCache(Character $character) {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return;
        }

        $cache['skills'] = $this->fetchSkills($character->skills);

        Cache::put('character-attack-data-' . $character->id, $cache);
    }

    private function getToHitBase(Character $character, CharacterInformationBuilder $characterInformation, bool $voided = false): int {

        if (!$voided) {
            return $characterInformation->statMod($character->class->to_hit_stat);
        }

        return $character->{$character->class->to_hit_stat};
    }
}
