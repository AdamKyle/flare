<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Gem;
use App\Game\Core\Gems\Values\GemTypeValue;
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
        return [
            'id'                         => $gem->id,
            'tier'                       => $gem->tier,
            'name'                       => $gem->name,
            'primary_atonement_name'     => (new GemTypeValue($gem->primary_atonement_type))->getNameOfAtonement(),
            'secondary_atonement_name'   => (new GemTypeValue($gem->secondary_atonement_type))->getNameOfAtonement(),
            'tertiary_atonement_name'    => (new GemTypeValue($gem->tertiary_atonement_type))->getNameOfAtonement(),
            'primary_atonement_amount'   => $gem->primary_atonement_amount,
            'secondary_atonement_amount' => $gem->secondary_atonement_amount,
            'tertiary_atonement_amount'  => $gem->tertiary_atonement_amount,
        ];
    }
}
