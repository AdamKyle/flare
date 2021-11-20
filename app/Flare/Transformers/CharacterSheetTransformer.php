<?php

namespace App\Flare\Transformers;

use App\Game\Automation\Values\AutomationType;
use Cache;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Values\View\ClassBonusInformation;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class CharacterSheetTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'                => $character->id,
            'name'              => $character->name,
            'attack'            => number_format($characterInformation->buildTotalAttack()),
            'health'            => number_format($characterInformation->buildHealth()),
            'ac'                => number_format($characterInformation->buildDefence()),
            'heal_for'          => number_format($characterInformation->buildHealFor()),
            'skills'            => $this->fetchSkills($character->skills),
            'damage_stat'       => $character->damage_stat,
            'to_hit_stat'       => $character->class->to_hit_stat,
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'inventory_max'     => $character->inventory_max,
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => $character->xp,
            'xp_next'           => $character->xp_next,
            'str'               => number_format($character->str),
            'dur'               => number_format($character->dur),
            'dex'               => number_format($character->dex),
            'chr'               => number_format($character->chr),
            'int'               => number_format($character->int),
            'agi'               => number_format($character->agi),
            'focus'             => number_format($character->focus),
            'str_modded'        => number_format(round($characterInformation->statMod('str'))),
            'dur_modded'        => number_format(round($characterInformation->statMod('dur'))),
            'dex_modded'        => number_format(round($characterInformation->statMod('dex'))),
            'chr_modded'        => number_format(round($characterInformation->statMod('chr'))),
            'int_modded'        => number_format(round($characterInformation->statMod('int'))),
            'agi_modded'        => number_format(round($characterInformation->statMod('agi'))),
            'focus_modded'      => number_format(round($characterInformation->statMod('focus'))),
            'spell_evasion'     => $characterInformation->getTotalDeduction('spell_evasion'),
            'artifact_anull'    => $characterInformation->getTotalDeduction('artifact_annulment'),
            'healing_reduction' => $characterInformation->getTotalDeduction('healing_reduction'),
            'affix_damage_red'  => $characterInformation->getTotalDeduction('affix_damage_reduction'),
            'res_chance'        => $characterInformation->fetchResurrectionChance(),
            'weapon_attack'     => number_format($characterInformation->getTotalWeaponDamage()),
            'rings_attack'      => number_format($characterInformation->getTotalRingDamage()),
            'spell_damage'      => number_format($characterInformation->getTotalSpellDamage()),
            'artifact_damage'   => number_format($characterInformation->getTotalArtifactDamage()),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'force_name_change' => $character->force_name_change,
            'timeout_until'     => $character->user->timeout_until,
            'class_bonus'       => (new ClassBonusInformation())->buildClassBonusDetails($character),
            'inventory_used'    => $character->getInventoryCount(),
            'inventory_max'     => $character->inventory_max,
            'can_adventure'     => $character->can_adventure,
            'is_dead'           => $character->is_dead,
            'devouring_light'   => $characterInformation->getDevouringLight(),
            'devouring_darkness' => $characterInformation->getDevouringDarkness(),
            'attack_stats'       => Cache::get('character-attack-data-' . $character->id),
            'automations'        => $this->getAutomations($character),
        ];
    }

    protected function getMaxLevel(Character $character) {
        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::CONTNUE_LEVELING;
        })->first();

        if (!is_null($slot)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }

    protected function getAutomations(Character $character): Collection {
        return $character->currentAutomations->transform(function($automation) {
            $automation->type = (new AutomationType($automation->type))->isAttack() ? 'attack' : 'Unknown';

            return $automation;
        });
    }
}
