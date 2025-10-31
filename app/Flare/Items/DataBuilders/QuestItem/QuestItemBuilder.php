<?php

namespace App\Flare\Items\DataBuilders\QuestItem;

use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Models\Item;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ItemResource;

class QuestItemBuilder {

    public function __construct(private readonly Manager $manager, private readonly QuestItemTransformer $questItemTransformer) {}

    public function createDataObject(Item $item): array {
        $questItem = new ItemResource($item, $this->questItemTransformer);

        return $this->manager->createData($questItem)->toArray()['data'];
    }
}
