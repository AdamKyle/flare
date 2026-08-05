<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\Kingdom;
use League\Fractal\TransformerAbstract;

class CondensedKingdomTransformer extends TransformerAbstract
{
    public function transform(Kingdom $kingdom): array
    {
        return [
            'id' => $kingdom->id,
            'name' => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
        ];
    }
}
