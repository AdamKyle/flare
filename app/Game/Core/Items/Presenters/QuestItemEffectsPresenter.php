<?php

namespace App\Game\Core\Items\Presenters;

use App\Game\Core\Items\Values\ItemEffectType;

final class QuestItemEffectsPresenter
{
    /**
     * Returns human-readable string of the quest item effect.
     */
    public function getEffect(?string $rawEffect): string
    {
        if ($rawEffect === null || $rawEffect === '') {
            return 'N/A';
        }

        $effect = ItemEffectType::tryFrom($rawEffect);

        return $effect?->label() ?? 'N/A';
    }
}
