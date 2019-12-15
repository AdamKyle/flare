<?php

namespace App\Flare\Transformers;

use Illuminate\Database\Eloquent\Collection;
use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;

class ShopTransformer extends TransformerAbstract {

    public function transform(Collection $items) {
        return [
            'weapons'   => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'weapon')->get(),
            'armour'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->whereIn('type', [
                'body', 'leggings', 'sleeves', 'gloves', 'helmet', 'shield'
            ])->get(),

            'artifacts' => Item::with('artifactProperty')->where('type', 'artifact')->get(),
            'spells'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'spell')->get(),
            'rings'     => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'ring')->get(),
        ];
    }
}
