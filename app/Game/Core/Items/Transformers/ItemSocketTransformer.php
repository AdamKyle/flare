<?php

namespace App\Game\Core\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\ItemSocket;
use App\Game\Gems\Transformers\CharacterGemTransformer;

class ItemSocketTransformer
{
    /**
     * @param CharacterGemTransformer $characterGemTransformer
     */
    public function __construct(private readonly CharacterGemTransformer $characterGemTransformer) {}

    /**
     * Transform attached Item socket rows in their authoritative creation order.
     *
     * @param Item $item
     * @return array
     */
    public function transform(Item $item): array
    {
        $item->loadMissing('sockets.gem.characterModifiers.gameGemAbility');

        return $item->sockets
            ->sortBy('id')
            ->values()
            ->map(fn (ItemSocket $socket, int $index): array => [
                'id' => $socket->id,
                'position' => $index + 1,
                'gem' => is_null($socket->gem)
                    ? null
                    : $this->characterGemTransformer->transform($socket->gem),
            ])
            ->all();
    }
}
