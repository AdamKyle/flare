<?php

namespace App\Flare\Transformers;

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
        return [
            'id'                => $character->id,
            'name'              => $character->name,
            'attack'            => number_format($this->fetchStats($character, 'attack')),
            'health'            => number_format($this->fetchStats($character, 'health')),
            'ac'                => number_format($this->fetchStats($character, 'ac')),
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => (int) $character->xp,
            'xp_next'           => (int) $character->xp_next,
            'str_modded'        => number_format($this->fetchStats($character, 'str_modded')),
            'dur_modded'        => number_format($this->fetchStats($character, 'dur_modded')),
            'dex_modded'        => number_format($this->fetchStats($character, 'dex_modded')),
            'chr_modded'        => number_format($this->fetchStats($character, 'chr_modded')),
            'int_modded'        => number_format($this->fetchStats($character, 'int_modded')),
            'agi_modded'        => number_format($this->fetchStats($character, 'agi_modded')),
            'focus_modded'      => number_format($this->fetchStats($character, 'focus_modded')),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'copper_coins'      => number_format($character->copper_coins),
            'is_dead'           => $character->is_dead,
            'can_adventure'     => $character->can_adventure,
        ];
    }
}
