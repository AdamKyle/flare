<?php

namespace App\Admin\Items\Transformers;

use App\Admin\Items\Values\ItemPresentationKind;
use App\Flare\Models\Item;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Values\ItemCatalogType;

class ItemDetailTransformer
{
    /**
     * @param  ItemTransformer  $itemTransformer  Canonical equipment/catalog Item transformer.
     * @param  QuestItemTransformer  $questItemTransformer  Canonical quest Item transformer.
     * @param  UsableItemTransformer  $usableItemTransformer  Canonical usable/alchemy Item transformer; the same transformer the player-facing usable Item presentation uses, so both contexts render identical factual fields.
     */
    public function __construct(
        private readonly ItemTransformer $itemTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly UsableItemTransformer $usableItemTransformer,
    ) {}

    /**
     * Transform an Item into its Admin detail representation.
     *
     * Delegates presentation data to the existing authoritative 2.0 Item
     * transformer for the resolved presentation kind, and keeps admin
     * catalog-management metadata separate from that player-facing data.
     *
     * @param  Item  $item  Item to transform.
     * @return array{id: int, name: string, type: string, presentation_kind: string, presentation: array<string, mixed>, management: array<string, mixed>} Admin Item detail representation.
     */
    public function transform(Item $item): array
    {
        $presentationKind = $this->resolvePresentationKind($item);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'presentation_kind' => $presentationKind->value,
            'presentation' => $this->resolvePresentation($item, $presentationKind),
            'management' => $this->resolveManagement($item),
        ];
    }

    /**
     * Resolve the presentation kind for the given Item using the same
     * classification the authoritative Item enrichment domain already uses.
     *
     * @param  Item  $item  Item to classify.
     * @return ItemPresentationKind Resolved presentation kind.
     */
    private function resolvePresentationKind(Item $item): ItemPresentationKind
    {
        if ($item->usable) {
            return ItemPresentationKind::USABLE;
        }

        if ($item->type === ItemCatalogType::QUEST->value) {
            return ItemPresentationKind::QUEST;
        }

        return ItemPresentationKind::EQUIPPABLE;
    }

    /**
     * Delegate presentation data to the existing authoritative transformer for the resolved kind.
     *
     * @param  Item  $item  Item to transform.
     * @param  ItemPresentationKind  $presentationKind  Resolved presentation kind.
     * @return array<string, mixed> Presentation data for the resolved kind.
     */
    private function resolvePresentation(Item $item, ItemPresentationKind $presentationKind): array
    {
        return match ($presentationKind) {
            ItemPresentationKind::USABLE => $this->usableItemTransformer->transform($item),
            ItemPresentationKind::QUEST => $this->questItemTransformer->transform($item),
            ItemPresentationKind::EQUIPPABLE => $this->itemTransformer->transform($item),
        };
    }

    /**
     * Build the admin-only catalog-management metadata for the given Item.
     *
     * @param  Item  $item  Item to describe.
     * @return array{can_craft: bool, craft_only: bool, crafting_type: string|null, market_sellable: bool, can_drop: bool, default_position: string|null, specialty_type: string|null, alchemy_type: string|null, skill_level_required: int|null, skill_level_trivial: int|null, unlocks_class: array{id: int, name: string}|null, item_skill: array{id: int, name: string}|null, is_generated_variant: bool} Catalog-management metadata.
     */
    private function resolveManagement(Item $item): array
    {
        return [
            'can_craft' => $item->can_craft,
            'craft_only' => $item->craft_only,
            'crafting_type' => $item->crafting_type,
            'market_sellable' => $item->market_sellable,
            'can_drop' => $item->can_drop,
            'default_position' => $item->default_position,
            'specialty_type' => $item->specialty_type,
            'alchemy_type' => $item->alchemy_type,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'unlocks_class' => $this->transformRelated($item->unlocksClass, fn ($class) => $class->name),
            'item_skill' => $this->transformRelated($item->itemSkill, fn ($skill) => $skill->name),
            'is_generated_variant' => ! is_null($item->item_prefix_id) || ! is_null($item->item_suffix_id) || ! is_null($item->parent_id),
        ];
    }

    /**
     * Transform a related model into its compact identity representation.
     *
     * @param  mixed  $related  Related model, when one is set.
     * @param  callable  $nameResolver  Resolves the display name from the related model.
     * @return array{id: int, name: string}|null Compact related identity.
     */
    private function transformRelated(mixed $related, callable $nameResolver): ?array
    {
        if (is_null($related)) {
            return null;
        }

        return [
            'id' => $related->id,
            'name' => $nameResolver($related),
        ];
    }
}
