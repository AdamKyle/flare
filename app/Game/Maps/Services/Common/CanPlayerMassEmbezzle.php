<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Character;
use App\Game\Core\Items\Values\ItemEffectType;

trait CanPlayerMassEmbezzle
{
    /**
     * Can we mass embezzle?
     */
    public function canMassEmbezzle(Character $character, bool $canManage): bool
    {
        $hasItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::MASS_EMBEZZLE->value && $slot->item->type === 'quest';
        })->first();

        return $hasItem && ! $character->is_dead && ! $character->is_mass_embezzling && $canManage;
    }
}
