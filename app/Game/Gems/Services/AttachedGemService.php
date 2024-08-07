<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Core\Traits\ResponseBuilder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class AttachedGemService
{
    use ResponseBuilder;

    private CharacterGemsTransformer $gemsTransformer;

    private Manager $manager;

    private CharacterInventoryService $characterInventoryService;

    public function __construct(CharacterGemsTransformer $gemsTransformer, Manager $manager, CharacterInventoryService $characterInventoryService)
    {
        $this->gemsTransformer = $gemsTransformer;
        $this->manager = $manager;
        $this->characterInventoryService = $characterInventoryService;
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
                $gemData = $this->manager->createData($gemData)->toArray();

                $socketData[] = $gemData;
            }
        }

        return $this->successResult([
            'socketed_gems' => $socketData,
        ]);
    }
}
