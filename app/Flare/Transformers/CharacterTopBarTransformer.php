<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

class CharacterTopBarTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'attack'            => $characterInformation->buildTotalAttack(),
            'health'            => $characterInformation->buildHealth(),
            'ac'                => $characterInformation->buildDefence(),
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => (int) $character->xp,
            'xp_next'           => (int) $character->xp_next,
            'str_modded'        => round($characterInformation->statMod('str')),
            'dur_modded'        => round($characterInformation->statMod('dur')),
            'dex_modded'        => round($characterInformation->statMod('dex')),
            'chr_modded'        => round($characterInformation->statMod('chr')),
            'int_modded'        => round($characterInformation->statMod('int')),
            'agi_modded'        => round($characterInformation->statMod('agi')),
            'focus_modded'      => round($characterInformation->statMod('focus')),
            'inventory_max'     => $character->inventory_max,
            'inventory_count'   => $character->getInventoryCount(),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'copper_coins'      => number_format($character->copper_coins),
            'is_silenced'       => $character->user->is_silenced,
            'can_talk_again_at' => $character->user->can_talk_again_at,
            'force_name_change' => $character->force_name_change,
            'is_banned'         => $character->user->is_banned,
        ];
    }
}
