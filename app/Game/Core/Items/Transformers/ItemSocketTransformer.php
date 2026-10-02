<?php

namespace App\Game\Core\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\ItemSocket;
use App\Game\Gems\Contracts\CharacterGemDetails;
use App\Game\Gems\Values\CharacterGemSnapshot;

class ItemSocketTransformer
{
    /**
     * @param CharacterGemDetails $characterGemDetails
     */
    public function __construct(private readonly CharacterGemDetails $characterGemDetails) {}

    /**
     * Transform attached Item socket rows in their authoritative creation order.
     *
     * @param Item $item
     * @return array
     */
    public function transform(Item $item): array
    {
        $item->loadMissing('sockets');

        $sockets = $item->sockets->sortBy('id')->values();

        $gemSnapshots = collect($this->characterGemDetails->forGemIds($sockets->pluck('gem_id')->filter()->unique()->values()->all()))
            ->keyBy(fn (CharacterGemSnapshot $gemSnapshot): int => $gemSnapshot->id);

        return $sockets
            ->map(fn (ItemSocket $socket, int $index): array => [
                'id' => $socket->id,
                'position' => $index + 1,
                'gem' => $gemSnapshots->get($socket->gem_id)?->toArray(),
            ])
            ->all();
    }
}
