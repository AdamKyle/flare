<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GemBagSlot;
use App\Game\Gems\Values\GemTypeValue;
use League\Fractal\TransformerAbstract;

class CharacterGemSlotsTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the inventory sheet
     */
    public function transform(GemBagSlot $gemBagSlot): array
    {

        $gem = $gemBagSlot->gem;

        $highestValue = collect([
            $gem->primary_atonement_amount,
            $gem->secondary_atonement_amount,
            $gem->tertiary_atonement_amount,
        ])->max();

        $primaryAtonementName = (new GemTypeValue($gem->primary_atonement_type))->getNameOfAtonement();
        $secondaryAtonementName = (new GemTypeValue($gem->secondary_atonement_type))->getNameOfAtonement();
        $tertiaryAtonementName = (new GemTypeValue($gem->tertiary_atonement_type))->getNameOfAtonement();

        $highestValueName = match ($highestValue) {
            $gem->primary_atonement_amount => $primaryAtonementName,
            $gem->secondary_atonement_amount => $secondaryAtonementName,
            $gem->tertiary_atonement_amount => $tertiaryAtonementName,
        };

        return [
            'slot_id' => $gemBagSlot->id,
            'name' => $gemBagSlot->gem->name,
            'tier' => $gemBagSlot->gem->tier,
            'amount' => $gemBagSlot->amount,
            'weak_against' => GemTypeValue::getOppsiteForHalfDamage($highestValueName),
            'strong_against' => GemTypeValue::getOppsiteForDoubleDamage($highestValueName),
            'element_atoned_to' => $highestValueName,
            'element_atoned_to_amount' => $highestValue,
        ];
    }
}
