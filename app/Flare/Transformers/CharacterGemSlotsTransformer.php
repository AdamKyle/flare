<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GemBagSlot;
use League\Fractal\TransformerAbstract;

class CharacterGemSlotsTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the inventory sheet
     *
     * @param GemBagSlot $gemBagSlot
     * @return array
     */
    public function transform(GemBagSlot $gemBagSlot): array {
        return [
            'id'     => $gemBagSlot->id,
            'name'   => $gemBagSlot->gem->name,
            'tier'   => $gemBagSlot->gem->tier,
            'amount' => $gemBagSlot->amount,
        ];
    }
}
