<?php

namespace App\Flare\Items\Enricher;

use App\Flare\Items\Values\ArmourType;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Item;
use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\UsableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
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

    /**
     * Returns the appropriate item model, enriched if necessary.
     *
     * @param Item $item
     * @param string | null $damageStat
     * @return Item
     */
    public function buildItem(Item $item, ?string $damageStat = null): Item
    {
        if ($this->isEquippable($item)) {
            return $this->equippableEnricher->enrich($item, $damageStat);
        }

        return $item;
    }

    /**
     * Returns a transformed array version of the item.
     *
     * @param Item $item
     * @return array
     */
    public function buildItemData(Item $item): array
    {
        if ($this->isEquippable($item)) {
            $enriched = $this->equippableEnricher->enrich($item);

            return $this->transform($enriched, $this->equippableTransformer);
        }

        if ($this->isUsable($item)) {
            return $this->transform($item, $this->usableTransformer);
        }

        if ($this->isQuest($item)) {
            return $this->transform($item, $this->questTransformer);
        }

        return [];
    }

    /**
     * Transforms an item using League Fractal.
     *
     * @param Item $item
     * @param mixed $transformer
     * @return array
     */
    private function transform(Item $item, mixed $transformer): array
    {
        $resource = new FractalItem($item, $transformer);

        return $this->manager->setSerializer($this->plainDataSerializer)->createData($resource)->toArray();
    }

    /**
     * Is the item equippable?
     *
     * @param Item $item
     * @return bool
     */
    private function isEquippable(Item $item): bool
    {

        $isEquippable = !$item->usable && (
                in_array($item->type, ItemType::allTypes()) ||
                in_array($item->type, ArmourType::allTypes())
            );

        return $isEquippable;
    }


    /**
     * Can we use the item on our self?
     *
     * @param Item $item
     * @return bool
     */
    private function isUsable(Item $item): bool
    {
        return $item->usable;
    }

    /**
     * Is the item a quest item?
     *
     * @param Item $item
     * @return bool
     */
    private function isQuest(Item $item): bool
    {
        return !$item->usable && $item->type === 'quest';
    }
}
