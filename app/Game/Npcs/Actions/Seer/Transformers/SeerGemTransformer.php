<?php

namespace App\Game\Npcs\Actions\Seer\Transformers;

use App\Flare\Models\GemBagSlot;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use League\Fractal\TransformerAbstract;

class SeerGemTransformer extends TransformerAbstract
{
    /**
     * @param CharacterGemTransformer $gemTransformer
     */
    public function __construct(private readonly CharacterGemTransformer $gemTransformer) {}

    /**
     * Transform a Gem Bag slot for Seer Gem selection.
     *
     * @param GemBagSlot $slot
     * @return array
     */
    public function transform(GemBagSlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'amount' => $slot->amount,
            'gem' => $this->gemTransformer->transform($slot->gem),
        ];
    }
}
