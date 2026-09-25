<?php

namespace App\Game\Core\Items\Enricher;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ItemEnricherFactory
{
    /**
     * @param EquippableEnricher $equippableEnricher
     * @param EquippableItemTransformer $equippableTransformer
     * @param UsableItemTransformer $usableTransformer
     * @param QuestItemTransformer $questTransformer
     * @param PlainDataSerializer $plainDataSerializer
     * @param Manager $manager
     */
    public function __construct(
        private readonly EquippableEnricher $equippableEnricher,
        private readonly EquippableItemTransformer $equippableTransformer,
        private readonly UsableItemTransformer $usableTransformer,
        private readonly QuestItemTransformer $questTransformer,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly Manager $manager,
    ) {}

    /**
     * Enrich an equippable item with its calculated attributes.
     *
     * @param Item $item
     * @param string|null $damageStat
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
     * Build transformed detail data for an item and optional owning slot.
     *
     * @param Item $item
     * @param InventorySlot|SetSlot|null $slot
     * @return array
     */
    public function buildItemData(Item $item, InventorySlot|SetSlot|null $slot = null): array
    {
        if (! is_null($slot) && $this->isEquippable($slot->item)) {
            $slot->item->loadMissing(['itemSkill.children', 'itemSkillProgressions.itemSkill']);
            $enriched = $this->equippableEnricher->enrich($slot->item);
            $slot->setRelation('item', $enriched);

            return $this->appendItemSkillData(
                $this->transform($slot, $this->equippableTransformer),
                $slot->item
            );
        }

        if ($this->isEquippable($item)) {
            $item->loadMissing(['itemSkill.children', 'itemSkillProgressions.itemSkill']);
            $enriched = $this->equippableEnricher->enrich($item);

            return $this->appendItemSkillData(
                $this->transform($enriched, $this->equippableTransformer),
                $item
            );
        }

        if (! is_null($slot) && $this->isUsable($item)) {
            $transformedItem = $this->transform($item, $this->usableTransformer);
            $slotArray = $slot->toArray();
            $slotArray['item'] = $transformedItem;

            return $slotArray;
        }

        if (! is_null($slot) && $this->isQuest($item)) {
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

    /**
     * Append item-local skill tree data to an equippable detail payload.
     *
     * @param array $data
     * @param Item $item
     * @return array
     */
    private function appendItemSkillData(array $data, Item $item): array
    {
        $data['item_skills'] = is_null($item->itemSkill) ? [] : [$item->itemSkill];
        $data['item_skill_progressions'] = $item->itemSkillProgressions;

        return $data;
    }

    /**
     * Transform a resource with the supplied item transformer.
     *
     * @param mixed $resource
     * @param mixed $transformer
     * @return array
     */
    private function transform(mixed $resource, mixed $transformer): array
    {
        $resource = new FractalItem($resource, $transformer);

        return $this->manager->setSerializer($this->plainDataSerializer)->createData($resource)->toArray();
    }

    /**
     * Determine whether an item can be equipped.
     *
     * @param Item $item
     * @return bool
     */
    private function isEquippable(Item $item): bool
    {
        return ! $item->usable && (
            in_array($item->type, ItemType::allTypes()) ||
            in_array($item->type, ArmourType::allTypes())
        );
    }

    /**
     * Determine whether an item is usable.
     *
     * @param Item $item
     * @return bool
     */
    private function isUsable(Item $item): bool
    {
        return $item->usable;
    }

    /**
     * Determine whether an item is a quest item.
     *
     * @param Item $item
     * @return bool
     */
    private function isQuest(Item $item): bool
    {
        return ! $item->usable && $item->type === 'quest';
    }
}
