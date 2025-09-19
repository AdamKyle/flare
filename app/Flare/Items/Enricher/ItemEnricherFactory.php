<?php

namespace App\Flare\Items\Enricher;

use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Items\Transformers\UsableItemTransformer;
use App\Flare\Items\Values\ArmourType;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ItemEnricherFactory
{
    public function __construct(
        private readonly EquippableEnricher $equippableEnricher,
        private readonly EquippableItemTransformer $equippableTransformer,
        private readonly UsableItemTransformer $usableTransformer,
        private readonly QuestItemTransformer $questTransformer,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly Manager $manager,
    ) {}

    public function buildItem(Item $item, ?string $damageStat = null): Item
    {
        if ($this->isEquippable($item)) {
            return $this->equippableEnricher->enrich($item, $damageStat);
        }

        return $item;
    }

    public function buildItemData(Item $item, InventorySlot|SetSlot|null $slot = null): array
    {
        if (!is_null($slot) && $this->isEquippable($slot->item)) {
            $enriched = $this->equippableEnricher->enrich($slot->item);
            $slot->setRelation('item', $enriched);
            return $this->transform($slot, $this->equippableTransformer);
        }

        if ($this->isEquippable($item)) {
            $enriched = $this->equippableEnricher->enrich($item);
            return $this->transform($enriched, $this->equippableTransformer);
        }

        if (!is_null($slot) && $this->isUsable($item)) {
            $transformedItem = $this->transform($item, $this->usableTransformer);
            $slotArray = $slot->toArray();
            $slotArray['item'] = $transformedItem;
            return $slotArray;
        }

        if (!is_null($slot) && $this->isQuest($item)) {
            $transformedItem = $this->transform($item, $this->questTransformer);
            $slotArray = $slot->toArray();
            $slotArray['item'] = $transformedItem;
            return $slotArray;
        }

        if ($this->isUsable($item)) {
            return $this->transform($item, $this->usableTransformer);
        }

        if ($this->isQuest($item)) {
            return $this->transform($item, $this->questTransformer);
        }

        return [];
    }

    private function transform(mixed $resource, mixed $transformer): array
    {
        $resource = new FractalItem($resource, $transformer);
        return $this->manager->setSerializer($this->plainDataSerializer)->createData($resource)->toArray();
    }

    private function isEquippable(Item $item): bool
    {
        return !$item->usable && (
                in_array($item->type, ItemType::allTypes()) ||
                in_array($item->type, ArmourType::allTypes())
            );
    }

    private function isUsable(Item $item): bool
    {
        return $item->usable;
    }

    private function isQuest(Item $item): bool
    {
        return !$item->usable && $item->type === 'quest';
    }
}
