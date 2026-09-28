<?php

namespace App\Game\Character\CharacterInventory\Transformers;

use App\Flare\Models\GemBagSlot;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use League\Fractal\TransformerAbstract;

class CharacterGemSlotsTransformer extends TransformerAbstract
{
    /**
     * @param CharacterGemTransformer $characterGemTransformer
     */
    public function __construct(private readonly CharacterGemTransformer $characterGemTransformer) {}

    /**
     * Transform a Gem Bag slot into its Gem representation plus slot identity and stacked amount.
     *
     * @param GemBagSlot $gemBagSlot
     * @return array
     */
    public function transform(GemBagSlot $gemBagSlot): array
    {
        return [
            'slot_id' => $gemBagSlot->id,
            'amount' => $gemBagSlot->amount,
            ...$this->characterGemTransformer->transform($gemBagSlot->gem),
        ];
    }
}
