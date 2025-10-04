<?php

namespace App\Flare\Items\Presenters;

use App\Flare\Items\Values\QuestItemEffectsType;

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

        $effect = QuestItemEffectsType::tryFrom($rawEffect);

        return $effect?->label() ?? 'N/A';
    }
}
