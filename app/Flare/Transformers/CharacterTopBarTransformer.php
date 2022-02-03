<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

class CharacterTopBarTransformer extends TransformerAbstract {

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
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => $character->xp,
            'xp_next'           => $character->xp_next,
            'str_modded'        => number_format(round($characterInformation->statMod('str'))),
            'dur_modded'        => number_format(round($characterInformation->statMod('dur'))),
            'dex_modded'        => number_format(round($characterInformation->statMod('dex'))),
            'chr_modded'        => number_format(round($characterInformation->statMod('chr'))),
            'int_modded'        => number_format(round($characterInformation->statMod('int'))),
            'agi_modded'        => number_format(round($characterInformation->statMod('agi'))),
            'focus_modded'      => number_format(round($characterInformation->statMod('focus'))),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'is_dead'           => $character->is_dead,
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
}
