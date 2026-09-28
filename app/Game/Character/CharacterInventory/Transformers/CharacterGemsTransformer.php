<?php

namespace App\Game\Character\CharacterInventory\Transformers;

use App\Flare\Models\Gem;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use League\Fractal\TransformerAbstract;

class CharacterGemsTransformer extends TransformerAbstract
{
    /**
     * @param CharacterGemTransformer $characterGemTransformer
     */
    public function __construct(private readonly CharacterGemTransformer $characterGemTransformer) {}

    /**
     * Transform a Character's Gem into its generic three-modifier inventory representation.
     *
     * @param Gem $gem
     * @return array
     */
    public function transform(Gem $gem): array
    {
        return $this->characterGemTransformer->transform($gem);
    }
}
