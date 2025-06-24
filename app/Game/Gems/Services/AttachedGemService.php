<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Core\Traits\ResponseBuilder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class AttachedGemService
{
    use ResponseBuilder;

    /**
     * @param CharacterGemsTransformer $gemsTransformer
     * @param Manager $manager
     * @param PlainDataSerializer $plainDataSerializer
     * @param CharacterInventoryService $characterInventoryService
     */
    public function __construct(
        private readonly CharacterGemsTransformer $gemsTransformer,
        private readonly Manager $manager,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly CharacterInventoryService $characterInventoryService)
    {
    }

    public function getGemsFromItem(Character $character, Item $item): array
    {
        $slot = $this->characterInventoryService->getSlotForItemDetails($character, $item);

        if (is_null($slot)) {
            return $this->errorResult('No item found in your inventory.');
        }

        $socketData = [];

        if ($item->sockets->isNotEmpty()) {
            foreach ($item->sockets as $socket) {
                $gemData = new FractalItem($socket->gem, $this->gemsTransformer);

                $this->manager->setSerializer($this->plainDataSerializer);

                $gemData = $this->manager->createData($gemData)->toArray();

                $socketData[] = $gemData;
            }
        }

        return $this->successResult([
            'socketed_gems' => $socketData,
        ]);
    }
}
