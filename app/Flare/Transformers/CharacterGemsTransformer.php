<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Gem;
use App\Game\Gems\Values\GemTypeValue;
use Exception;
use League\Fractal\TransformerAbstract;

class CharacterGemsTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the inventory sheet
     *
     * @param Gem $gem gem
     * @return array
     * @throws Exception
     */
    public function transform(Gem $gem): array {

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
            'id'                         => $gem->id,
            'tier'                       => $gem->tier,
            'name'                       => $gem->name,
            'primary_atonement_name'     => $primaryAtonementName,
            'secondary_atonement_name'   => $secondaryAtonementName,
            'tertiary_atonement_name'    => $tertiaryAtonementName,
            'primary_atonement_amount'   => $gem->primary_atonement_amount,
            'secondary_atonement_amount' => $gem->secondary_atonement_amount,
            'tertiary_atonement_amount'  => $gem->tertiary_atonement_amount,
            'weak_against'               => GemTypeValue::getOppsiteForHalfDamage($highestValueName),
            'strong_against'             => GemTypeValue::getOppsiteForDoubleDamage($highestValueName),
            'element_atoned_to'          => $highestValueName,
            'element_atoned_to_amount'   => $highestValue,
        ];
    }
}
