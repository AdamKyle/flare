<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Game\Gems\Contracts\GemAbilityDuplicateQuery;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\DuplicateGemAbility;
use App\Game\Gems\Values\DuplicateGemAbilityItem;
use App\Game\Gems\Values\DuplicateGemAbilityReport;
use App\Game\Gems\Values\GemAbilityOccurrence;
use App\Game\Gems\Values\GemAbilityOwnershipSource;
use App\Game\Gems\Values\GemAbilityType;
use App\Game\Gems\Values\OwnedGemItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class GemAbilityDuplicateService implements GemAbilityDuplicateQuery
{
    /**
     * Return the first deterministic Active Gem Ability modifier id for every ability across the Items.
     *
     * @param iterable $items
     * @return array
     */
    public function firstActiveModifierIds(iterable $items): array
    {
        $ownedGemItems = collect($items)
            ->filter()
            ->map(fn (Item $item): OwnedGemItem => new OwnedGemItem($item, GemAbilityOwnershipSource::INVENTORY))
            ->all();

        return array_values(array_map(
            fn (array $occurrences): int => $occurrences[0]->modifier->id,
            $this->groupActiveAbilityOccurrences($ownedGemItems),
        ));
    }

    /**
     * Return duplicate Active Gem Abilities socketed within a single Item.
     *
     * @param int $itemId
     * @return DuplicateGemAbilityReport
     */
    public function forItem(int $itemId): DuplicateGemAbilityReport
    {
        $item = Item::with($this->itemRelationships())->find($itemId);

        if (is_null($item)) {
            return new DuplicateGemAbilityReport([]);
        }

        return $this->buildReport([new OwnedGemItem($item, GemAbilityOwnershipSource::INVENTORY)]);
    }

    /**
     * Return the Character's duplicate Active Gem Abilities that include the given owned Item.
     *
     * @param int $characterId
     * @param int $itemId
     * @return DuplicateGemAbilityReport
     */
    public function forOwnedItem(int $characterId, int $itemId): DuplicateGemAbilityReport
    {
        $duplicates = $this->buildReport($this->ownedGemItems($characterId))->duplicates;

        return new DuplicateGemAbilityReport(array_values(array_filter(
            $duplicates,
            fn (DuplicateGemAbility $duplicate): bool => $duplicate->includesItem($itemId),
        )));
    }

    /**
     * Return the Character's duplicate Active Gem Abilities that include an Item from the given Inventory Set.
     *
     * @param int $characterId
     * @param int $inventorySetId
     * @return DuplicateGemAbilityReport
     */
    public function forInventorySet(int $characterId, int $inventorySetId): DuplicateGemAbilityReport
    {
        $duplicates = $this->buildReport($this->ownedGemItems($characterId))->duplicates;

        return new DuplicateGemAbilityReport(array_values(array_filter(
            $duplicates,
            fn (DuplicateGemAbility $duplicate): bool => $duplicate->includesInventorySet($inventorySetId),
        )));
    }

    /**
     * Load every Item the Character owns across inventory, equipped gear, and Inventory Sets.
     *
     * @param int $characterId
     * @return array
     */
    private function ownedGemItems(int $characterId): array
    {
        $character = Character::with([
            'inventory.slots.item' => fn (Relation $query): Relation => $query->with($this->itemRelationships()),
            'inventorySets.slots.item' => fn (Relation $query): Relation => $query->with($this->itemRelationships()),
        ])->find($characterId);

        if (is_null($character)) {
            return [];
        }

        return [
            ...$this->inventoryGemItems($character),
            ...$this->inventorySetGemItems($character),
        ];
    }

    /**
     * Build the owned-Item entries for the Character's inventory and equipped slots.
     *
     * @param Character $character
     * @return array
     */
    private function inventoryGemItems(Character $character): array
    {
        return $character->inventory->slots
            ->filter(fn (InventorySlot $slot): bool => ! is_null($slot->item))
            ->map(fn (InventorySlot $slot): OwnedGemItem => new OwnedGemItem(
                $slot->item,
                $slot->equipped ? GemAbilityOwnershipSource::EQUIPPED : GemAbilityOwnershipSource::INVENTORY,
            ))
            ->values()
            ->all();
    }

    /**
     * Build the owned-Item entries for every Item stored in the Character's Inventory Sets.
     *
     * @param Character $character
     * @return array
     */
    private function inventorySetGemItems(Character $character): array
    {
        return $character->inventorySets
            ->flatMap(fn (InventorySet $inventorySet): array => $inventorySet->slots
                ->filter(fn (SetSlot $slot): bool => ! is_null($slot->item))
                ->map(fn (SetSlot $slot): OwnedGemItem => new OwnedGemItem(
                    $slot->item,
                    $inventorySet->is_equipped ? GemAbilityOwnershipSource::EQUIPPED : GemAbilityOwnershipSource::SET,
                    $inventorySet->id,
                    $inventorySet->name,
                ))
                ->values()
                ->all())
            ->values()
            ->all();
    }

    /**
     * Build the report of Active Gem Abilities that occur more than once across the owned Items.
     *
     * @param array $ownedGemItems
     * @return DuplicateGemAbilityReport
     */
    private function buildReport(array $ownedGemItems): DuplicateGemAbilityReport
    {
        $duplicateGroups = array_filter(
            $this->groupActiveAbilityOccurrences($ownedGemItems),
            fn (array $occurrences): bool => count($occurrences) > 1,
        );

        return new DuplicateGemAbilityReport(array_values(array_map(
            fn (array $occurrences): DuplicateGemAbility => $this->buildDuplicate($occurrences),
            $duplicateGroups,
        )));
    }

    /**
     * Group every socketed Active Gem Ability occurrence by canonical ability id, in deterministic Item order.
     *
     * @param array $ownedGemItems
     * @return array
     */
    private function groupActiveAbilityOccurrences(array $ownedGemItems): array
    {
        return collect($ownedGemItems)
            ->sortBy(fn (OwnedGemItem $ownedGemItem): int => $ownedGemItem->item->id)
            ->flatMap(fn (OwnedGemItem $ownedGemItem): array => $this->activeAbilityOccurrences($ownedGemItem))
            ->groupBy(fn (GemAbilityOccurrence $occurrence): int => $occurrence->modifier->game_gem_ability_id)
            ->map(fn (Collection $occurrences): array => $occurrences->all())
            ->all();
    }

    /**
     * Return the Active Gem Ability occurrences socketed into one owned Item, in socket and roll order.
     *
     * @param OwnedGemItem $ownedGemItem
     * @return array
     */
    private function activeAbilityOccurrences(OwnedGemItem $ownedGemItem): array
    {
        $ownedGemItem->item->loadMissing($this->itemRelationships());

        return $ownedGemItem->item->sockets
            ->sortBy('id')
            ->pluck('gem')
            ->filter(fn (?Gem $gem): bool => ! is_null($gem) && $gem->domain === Gem::DOMAIN_CHARACTER)
            ->flatMap(fn (Gem $gem): array => $gem->characterModifiers->sortBy(['roll_position', 'id'])->all())
            ->filter(fn (CharacterGemModifier $modifier): bool => $this->isActiveAbility($modifier))
            ->map(fn (CharacterGemModifier $modifier): GemAbilityOccurrence => new GemAbilityOccurrence($modifier, $ownedGemItem))
            ->values()
            ->all();
    }

    /**
     * Determine whether a modifier references an Active Gem Ability.
     *
     * @param CharacterGemModifier $modifier
     * @return bool
     */
    private function isActiveAbility(CharacterGemModifier $modifier): bool
    {
        return $modifier->modifier_type === CharacterGemModifierType::GEM_ABILITY
            && ! is_null($modifier->gameGemAbility)
            && $modifier->gameGemAbility->ability_type === GemAbilityType::ACTIVE;
    }

    /**
     * Build one duplicate Active Gem Ability group with each affected Item listed once.
     *
     * @param array $occurrences
     * @return DuplicateGemAbility
     */
    private function buildDuplicate(array $occurrences): DuplicateGemAbility
    {
        $ability = $occurrences[0]->modifier->gameGemAbility;

        $items = collect($occurrences)
            ->map(fn (GemAbilityOccurrence $occurrence): OwnedGemItem => $occurrence->ownedGemItem)
            ->unique(fn (OwnedGemItem $ownedGemItem): int => $ownedGemItem->item->id)
            ->map(fn (OwnedGemItem $ownedGemItem): DuplicateGemAbilityItem => DuplicateGemAbilityItem::fromOwnedGemItem($ownedGemItem))
            ->values()
            ->all();

        return new DuplicateGemAbility($ability->id, $ability->name, count($occurrences), $items);
    }

    /**
     * Return the Item relationships needed to resolve socketed Gem Abilities and affected-Item presentation.
     *
     * @return array
     */
    private function itemRelationships(): array
    {
        return [
            'itemPrefix',
            'itemSuffix',
            'appliedHolyStacks',
            'sockets.gem.characterModifiers.gameGemAbility',
        ];
    }
}
