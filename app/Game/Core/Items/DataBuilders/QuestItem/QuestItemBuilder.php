<?php

namespace App\Game\Core\Items\DataBuilders\QuestItem;

use App\Flare\Models\Item;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ItemResource;

class QuestItemBuilder
{
    public function __construct(private readonly Manager $manager, private readonly QuestItemTransformer $questItemTransformer) {}

    public function createDataObject(Item $item): array
    {
        $questItem = new ItemResource($item, $this->questItemTransformer);

        return $this->manager->createData($questItem)->toArray()['data'];
    }
}
