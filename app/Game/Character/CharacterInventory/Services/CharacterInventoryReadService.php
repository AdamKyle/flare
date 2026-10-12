<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class CharacterInventoryReadService
{
    /**
     * @param ItemEnricherFactory $itemEnricherFactory
     * @param EquippableItemTransformer $equippableItemTransformer
     * @param QuestItemTransformer $questItemTransformer
     * @param InventoryTransformer $inventoryTransformer
     * @param CharacterInventoryFilterService $characterInventoryFilterService
     * @param Pagination $pagination
     * @param Manager $manager
     */
    public function __construct(
        private readonly ItemEnricherFactory $itemEnricherFactory,
        private readonly EquippableItemTransformer $equippableItemTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly InventoryTransformer $inventoryTransformer,
        private readonly CharacterInventoryFilterService $characterInventoryFilterService,
        private readonly Pagination $pagination,
        private readonly Manager $manager,
    ) {}

    /**
     * Return enriched normal Inventory slots ordered by damage contribution.
     *
     * @param Character $character
     * @param string $searchText
     * @return Collection
     */
    public function inventoryCollection(Character $character, string $searchText = ''): Collection
    {
        return $this->inventorySlotsCollection($character, $searchText)
            ->map(function ($slot) use ($character) {
                $slot->item = $this->itemEnricherFactory->buildItem($slot->item, $character->damage_stat);

                return $slot;
            })
            ->sortByDesc(fn ($slot) => $slot->item->total_damage_stat_bonus)
            ->values();
    }

    /**
     * Return unequipped normal Inventory slots.
     *
     * @param Character $character
     * @param string $searchText
     * @return Collection
     */
    public function inventorySlotsCollection(Character $character, string $searchText = ''): Collection
    {
        $slotsToIgnore = Cache::get('character-slots-to-disenchant-'.$character->id, []);
        $slots = $character->inventory->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem'])
            ->whereNotIn('id', $slotsToIgnore)
            ->where('equipped', false);

        if ($searchText !== '') {
            $search = Str::lower($searchText);
            $slots = $slots->filter(function ($slot) use ($search): bool {
                return Str::contains(Str::lower($slot->item->name), $search)
                    || ($slot->item->itemPrefix && Str::contains(Str::lower($slot->item->itemPrefix->name), $search))
                    || ($slot->item->itemSuffix && Str::contains(Str::lower($slot->item->itemSuffix->name), $search));
            });
        }

        return new Collection($slots->values());
    }

    /**
     * Return paginated normal Inventory slots.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filters
     * @return array
     */
    public function fetchInventory(
        Character $character,
        int $perPage = 10,
        int $page = 1,
        string $searchText = '',
        array $filters = [],
    ): array {
        $slots = $this->characterInventoryFilterService->filter(
            $this->inventoryCollection($character, $searchText),
            $filters,
        );

        return $this->pagination->buildPaginatedDate($slots, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Return Quest Items, optionally filtered by name.
     *
     * @param Character $character
     * @param string $searchText
     * @return Collection
     */
    public function questItems(Character $character, string $searchText = ''): Collection
    {
        $slots = $character->inventory->slots->where('item.type', 'quest');

        if ($searchText !== '') {
            $slots = $slots->filter(fn ($slot): bool => Str::contains(Str::lower($slot->item->name), $searchText));
        }

        return new Collection($slots->map(fn ($slot) => $slot->item)->values());
    }

    /**
     * Return paginated Quest Items.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @return array
     */
    public function fetchQuestItems(Character $character, int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        return $this->pagination->buildPaginatedDate(
            $this->questItems($character, $searchText),
            $this->questItemTransformer,
            $perPage,
            $page,
        );
    }

    /**
     * Return transformed equipped normal Inventory or Inventory Set slots.
     *
     * @param Character $character
     * @return array|null
     */
    public function equipped(Character $character): ?array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $slots = InventorySlot::where('inventory_id', $inventory->id)
            ->where('equipped', true)
            ->with('item')
            ->get();

        $slots = $this->enrichSlots($slots);

        if ($slots->isNotEmpty()) {
            return $this->transformEquipped($slots);
        }

        $inventorySet = InventorySet::where('character_id', $character->id)->where('is_equipped', true)->first();

        if (is_null($inventorySet)) {
            return [];
        }

        $this->repairEquippedSetPositions($inventorySet);

        return $this->transformEquipped($this->enrichSlots(
            SetSlot::where('inventory_set_id', $inventorySet->id)->with('item')->get(),
        ));
    }

    /**
     * Return destroyable normal Inventory slot identifiers.
     *
     * @param Character $character
     * @return array
     */
    public function inventorySlotIds(Character $character): array
    {
        return $character->inventory->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem', 'artifact'])
            ->where('equipped', false)
            ->sortBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * Enrich slots containing existing Items.
     *
     * @param Collection $slots
     * @return Collection
     */
    private function enrichSlots(Collection $slots): Collection
    {
        return new Collection($slots
            ->filter(fn ($slot): bool => ! is_null($slot->item))
            ->map(function ($slot) {
                $slot->setRelation('item', $this->itemEnricherFactory->buildItem($slot->item));

                return $slot;
            })
            ->values());
    }

    /**
     * Transform equipped slots for API output.
     *
     * @param Collection $slots
     * @return array
     */
    private function transformEquipped(Collection $slots): array
    {
        return $this->manager->createData(new LeagueCollection($slots, $this->equippableItemTransformer))->toArray();
    }

    /**
     * Repair legacy equipped Inventory Set positions.
     *
     * @param InventorySet $inventorySet
     * @return void
     */
    private function repairEquippedSetPositions(InventorySet $inventorySet): void
    {
        $inventorySet->loadMissing('slots.item');

        foreach ($inventorySet->slots as $slot) {
            $position = match (ItemType::tryFrom($slot->item->type)) {
                ItemType::TRINKET => 'trinket',
                ItemType::ARTIFACT => 'artifact',
                default => null,
            };

            if (is_null($position) || ($slot->equipped === true && $slot->position === $position)) {
                continue;
            }

            $slot->update(['equipped' => true, 'position' => $position]);
        }
    }
}
