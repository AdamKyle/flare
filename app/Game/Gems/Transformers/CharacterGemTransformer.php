<?php

namespace App\Game\Gems\Transformers;

use App\Flare\Models\Gem;
use App\Game\Gems\Values\CharacterGemSnapshot;
use League\Fractal\TransformerAbstract;

class CharacterGemTransformer extends TransformerAbstract
{
    /**
     * Transform a character-domain Gem into its generic three-modifier representation.
     *
     * @param Gem $gem
     * @return array
     */
    public function transform(Gem $gem): array
    {
        return CharacterGemSnapshot::fromGem($gem)->toArray();
    }
}
