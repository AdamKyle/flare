<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class CharacterInventoryService {

    use ResponseBuilder;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var InventorySlot $inventorySlot
     */
    private $inventorySlot;

    /**
     * @var array $positions
     */
    private $positions;

    /**
     * @var bool $isInventorySetIsEquipped
     */
    private bool $isInventorySetIsEquipped = false;

    /**
     * @var string $inventorySetEquippedName
     */
    private string $inventorySetEquippedName = '';

    /**
     * @var Collection $inventory
     */
    private Collection $inventory;

    /**
     * @var InventoryTransformer $inventoryTransformer
     */
    private InventoryTransformer $inventoryTransformer;

    /**
     * @var UsableItemTransformer $usableItemTransformer
     */
    private UsableItemTransformer $usableItemTransformer;

    /**
     * @var MassDisenchantService $massDisenchantService
     */
    private MassDisenchantService $massDisenchantService;

    /**
     * @var UpdateCharacterSkillsService $updateCharacterSkillsService
     */
    private UpdateCharacterSkillsService $updateCharacterSkillsService;

    /**
     * @var UpdateCharacterAttackTypesHandler  $updateCharacterAttackTypesHandler
     */
    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param InventoryTransformer $inventoryTransformer
     * @param UsableItemTransformer $usableItemTransformer
     * @param MassDisenchantService $massDisenchantService
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler
     * @param Manager $manager
     */
    public function __construct(
        InventoryTransformer  $inventoryTransformer,
        UsableItemTransformer $usableItemTransformer,
        MassDisenchantService $massDisenchantService,
        UpdateCharacterSkillsService $updateCharacterSkillsService,
        UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler,
        Manager $manager,

    ) {
        $this->inventoryTransformer         = $inventoryTransformer;
        $this->usableItemTransformer        = $usableItemTransformer;
        $this->massDisenchantService        = $massDisenchantService;
        $this->updateCharacterSkillsService = $updateCharacterSkillsService;
        $this->updateCharacterAttackTypesHandler = $updateCharacterAttackTypesHandler;
        $this->manager                      = $manager;
    }

    /**
     * Set the character
     *
     * @param Character $character
     * @return CharacterInventoryService
     */
    public function setCharacter(Character $character): CharacterInventoryService {
        $this->character = $character;

        return $this;
    }

    /**
     * Set the inventory slot
     *
     * @param InventorySlot $inventorySlot
     * @return CharacterInventoryService
     */
    public function setInventorySlot(InventorySlot $inventorySlot): CharacterInventoryService {
        $this->inventorySlot = $inventorySlot;

        return $this;
    }

    /**
     * Set the positions
     *
     * @param array $positions
     * @return CharacterInventoryService
     */
    public function setPositions(array $positions): CharacterInventoryService {
        $this->positions = $positions;

        return $this;
    }

    /**
     * Get api response.
     *
     * @return array
     */
    public function getInventoryForApi(): array {
        $equipped   = $this->fetchEquipped();
        $usableSets = $this->getUsableSets();

        return [
            'inventory'         => $this->fetchCharacterInventory(),
            'usable_sets'       => $usableSets,
            'savable_sets'      => $usableSets,
            'equipped'          => !is_null($equipped) ? $equipped : [],
            'sets'              => $this->getCharacterInventorySets(),
            'quest_items'       => $this->getQuestItems(),
            'usable_items'      => $this->getUsableItems(),
            'set_is_equipped'   => $this->isInventorySetIsEquipped,
            'set_name_equipped' => $this->inventorySetEquippedName,
        ];
    }

    /**
     * @param string $type
     * @return Collection|array
     */
    public function getInventoryForType(string $type): Collection|array {
        switch ($type) {
            case 'inventory':
                return $this->fetchCharacterInventory();
            case 'usable_sets':
            case 'savable_sets':
                return $this->getUsableSets();
            case 'equipped':
                $equipped   = $this->fetchEquipped();
                return !is_null($equipped) ? $equipped : [];
            case 'sets':
                return [
                    'sets'         => $this->getCharacterInventorySets(),
                    'set_equipped' => InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->count() > 0,
                ];
            case 'quest_items':
                return $this->getQuestItems();
            case 'usable_items':
                return $this->getUsableItems();
            default:
                return $this->getInventoryForApi();
        }
    }

    /**
     * Gets the slot that holds the item, for its details.
     *
     * @param Character $character
     * @param Item $item
     * @return InventorySlot|SetSlot|null
     */
    public function getSlotForItemDetails(Character $character, Item $item): InventorySlot | SetSlot | null {

        $slot = Inventory::where('character_id', $character->id)->first()->slots()->where('item_id', $item->id)->first();

        if (is_null($slot)) {

            $desiredSlot = $character->inventorySets()
                ->whereHas('slots', function($query) use ($item) {
                    $query->where('item_id', $item->id);
                })->first();

            if (is_null($desiredSlot)) {
                return null;
            }

            $slot = $desiredSlot->slots->filter(function ($slot) use ($item) {
                return $slot->item_id === $item->id;
            })->first();
        }

        return $slot;
    }

    /**
     * Disenchant all items in an inventory.
     *
     * @param Collection $slots
     * @param Character $character
     * @return array
     */
    public function disenchantAllItems(Collection $slots, Character $character): array {

        $maxedOutGoldDust = $character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST;

        $this->massDisenchantService->setUp($character)->disenchantItems($slots);

        $totalDisenchantingLevels = $this->massDisenchantService->getDisenchantingTimesLeveled();
        $totalEnchantingLevels    = $this->massDisenchantService->getEnchantingTimesLeveled();
        $totalGoldDust            = $this->massDisenchantService->getTotalGoldDust();

        $this->updateCharacterSkillsService->updateCharacterCraftingSkills($character->refresh());

        $message = 'Disenchanted all items and gained: ' . ($maxedOutGoldDust ? 0 . ' (You are capped ) ' :  number_format($totalGoldDust)) . ' Gold Dust (with gold dust rushes)';

        if ($totalDisenchantingLevels > 0) {
            $message .= ' You also gained: ' . $totalDisenchantingLevels . ' Skill Levels in Disenchanting.';
        }

        if ($totalEnchantingLevels > 0) {
            $message .= ' You also gained: ' . $totalEnchantingLevels . ' Skill Levels in Enchanting.';
        }

        return $this->successResult([
            'message' => $message
        ]);
    }

    /**
     * Get character inventory sets.
     *
     * @return array
     */
    public function getCharacterInventorySets(): array {
        $sets = [];

        foreach ($this->character->inventorySets as $index => $inventorySet) {

            $slots = new LeagueCollection($inventorySet->slots, $this->inventoryTransformer);

            if (is_null($inventorySet->name)) {
                $sets['Set ' . $index + 1] = [
                    'items'      => array_reverse($this->manager->createData($slots)->toArray()),
                    'equippable' => $inventorySet->can_be_equipped,
                    'set_id'     => $inventorySet->id,
                    'equipped'   => $inventorySet->is_equipped,
                ];
            } else {
                $sets[$inventorySet->name] = [
                    'items'      => array_reverse($this->manager->createData($slots)->toArray()),
                    'equippable' => $inventorySet->can_be_equipped,
                    'set_id'     => $inventorySet->id,
                    'equipped'   => $inventorySet->is_equipped,
                ];
            }
        }

        return $sets;
    }

    /**
     * Get equipped inventory set name.
     *
     * - Either null if none.
     * - Equipped set name.
     * - Equipped set string id + 1
     *
     * @return string|null
     */
    public function getEquippedInventorySetName(): string|null {
        $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (is_null($equippedSet)) {
            return null;
        }

        if (!is_null($equippedSet->name)) {
            return $equippedSet->name;
        }

        return 'Set ' . $this->character->inventorySets->search(function ($set) use ($equippedSet) {
            return $set->id === $equippedSet->id;
        }) + 1;
    }

    /**
     * Returns the usable items.
     *
     * @return array
     */
    public function getUsableItems(): array {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function ($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->where('items.type', 'alchemy');
        })->select('inventory_slots.*')->get();

        $slots = new LeagueCollection($slots, $this->usableItemTransformer);

        return $this->manager->createData($slots)->toArray();
    }

    /**
     * Returns the quest items.
     *
     * @return array
     */
    public function getQuestItems(): array {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function ($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->where('items.type', 'quest');
        })->select('inventory_slots.*')->get();

        $slots = new LeagueCollection($slots, $this->inventoryTransformer);

        return $this->manager->createData($slots)->toArray();
    }

    /**
     * Gets a list of usable slot's
     *
     * We return the index + 1 which refers to the slot number.
     * ie, index of 0, is Slot 1 and so on.
     *
     * @return array
     */
    public function getUsableSets(): array {
        $ids    = InventorySet::where('is_equipped', false)->where('character_id', $this->character->id)->pluck('id')->toArray();
        $setIds = InventorySet::where('character_id', $this->character->id)->pluck('id')->toArray();

        $indexes = [];

        foreach ($ids as $id) {
            $inventorySet = InventorySet::find($id);

            if (!$inventorySet->is_equipped) {

                $indexes[] = [
                    'index'    => array_search($id, $setIds) + 1,
                    'id'       => $id,
                    'name'     => is_null($inventorySet->name) ? 'Set ' . array_search($id, $setIds) + 1 : $inventorySet->name,
                    'equipped' => $inventorySet->is_equipped,
                ];
            }
        }

        return $indexes;
    }

    /**
     * Get inventory collection.
     *
     *  - Does not include equipped, usable or quest items.
     *  - Only comes from inventory, does not include sets.
     *
     * @return Collection
     */
    public function getInventoryCollection(): Collection {

        return $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy'])
            ->where('equipped', false);
    }

    /**
     * Fetches the characters inventory.
     *
     * - Does not include equipped, usable or quest items.
     * - Only comes from inventory, does not include sets.
     *
     * @return array
     */
    public function fetchCharacterInventory(): array {

        $slots = $this->getInventoryCollection();

        $slots = new LeagueCollection($slots, $this->inventoryTransformer);

        return array_reverse($this->manager->createData($slots)->toArray());
    }

    /**
     * Fetch inventory slot items.
     *
     * - Does not include alchemy or quest items.
     * - Items can also not be equipped.
     *
     * @return array
     */
    public function findCharacterInventorySlotIds(): array {

        return $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy'])
            ->where('equipped', false)
            ->sortBy('id')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Fetch equipped items.
     *
     * @return array
     */
    public function fetchEquipped(): array {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots     = InventorySlot::where('inventory_id', $inventory->id)->where('equipped', true)->get();

        if ($slots->isNotEmpty()) {
            $slots = new LeagueCollection($slots, $this->inventoryTransformer);

            return $this->manager->createData($slots)->toArray();
        }

        $inventorySet = InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->first();

        if (is_null($inventorySet)) {
            return [];
        }

        $this->isInventorySetIsEquipped = true;

        if (!is_null($inventorySet->name)) {
            $this->inventorySetEquippedName = $inventorySet->name;
        } else {
            $index = $this->character->inventorySets->search(function ($set) use ($inventorySet) {
                return $set->id === $inventorySet->id;
            });

            if ($index !== false) {
                $this->inventorySetEquippedName = 'Set ' . $index + 1;
            }
        }

        $slots = SetSlot::where('inventory_set_id', $inventorySet->id)->get();

        $slots = new LeagueCollection($slots, $this->inventoryTransformer);

        return $this->manager->createData($slots)->toArray();
    }

    /**
     * Set the inventory
     *
     * @return CharacterInventoryService
     */
    public function setInventory(): CharacterInventoryService {

        $this->inventory = $this->getInventory();

        return $this;
    }

    /**
     * Get inventory
     *
     * @return Collection
     */
    protected function getInventory(): Collection {

        $inventory = $this->character->inventory->slots()->whereIn('position', $this->positions)->get();

        if (!$inventory->isEmpty()) {
            return $inventory;
        }

        $result = $this->character->inventorySets()
            ->where('is_equipped', true)
            ->whereHas('slots', function ($query) {
                $query->whereIn('position', $this->positions);
            })
            ->get()
            ->pluck('slots')
            ->flatten();

        return new Collection($result);
    }

    /**
     * Return the inventory
     *
     * @return Collection
     */
    public function inventory(): Collection {
        return $this->inventory;
    }

    /**
     * Fetches the type of the item.
     *
     * @param Item $item
     * @return string
     * @throws Exception
     */
    public function getType(Item $item): string {
        return $this->fetchType($item->type);
    }

    /**
     * Delete an item from the inventory.
     *
     * @param int $slotId
     * @return array
     */
    public function deleteItem(int $slotId): array {
        $slot = $this->character->inventory->slots->filter(function ($slot) use ($slotId) {
            return $slot->id === $slotId;
        })->first();

        if (is_null($slot)) {
            return $this->errorResult('You don\'t own that item.');
        }

        if ($slot->equipped) {
            return $this->errorResult('Cannot destroy equipped item.');
        }

        $name = $slot->item->affix_name;

        $item = null;

        if ($slot->item->type === 'artifact' && $slot->item->itemSkillProgressions->isNotEmpty()) {
            $item = $slot->item;
        }

        $slot->delete();

        if (!is_null($item)) {
            $item->itemSkillProgressions()->delete();

            $item->delete();
        }

        return $this->successResult([
            'message' => 'Destroyed ' . $name . '.',
            'inventory' => [
                'inventory' => $this->getInventoryForType('inventory'),
            ]
        ]);
    }

    /**
     * Destroy all items in your inventory.
     *
     * - Will not destroy sets or items in sets.
     * - Will not destroy quest items or usable items.
     *
     * @return array
     */
    public function destroyAllItemsInInventory(): array {
        $slotIds   = $this->findCharacterInventorySlotIds();

        $items     = $this->character->inventory->slots->where('item.type', 'artifact')->whereNotNull('item.itemSkillProgressions')->pluck('item.id')->toArray();

        $this->character->inventory->slots()->whereIn('id', $slotIds)->delete();

        if (!empty($items)) {
            $items = Item::whereIn('id', $items)->get();

            foreach ($items as $item) {
                $item->itemSkillProgressions()->delete();

                $item->delete();
            }
        }

        return $this->successResult([
            'message' => 'Destroyed all items.',
            'inventory' => [
                'inventory' => $this->getInventoryForType('inventory'),
            ]
        ]);
    }

    /**
     * Disenchant all items in the characters inventory.
     *
     * @return array
     */
    public function disenchantAllItemsInInventory(): array {
        $slots   = $this->getInventoryCollection()->filter(function ($slot) {
            return (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id));
        })->values();

        if ($slots->isNotEmpty()) {

            return $this->disenchantAllItems($slots, $this->character);
        }

        return $this->successResult([
          'message' => 'You have nothing to disenchant.'
        ]);
    }

    /**
     * Unequip an item from the player.
     *
     * @param int $inventorySlotId
     * @return array
     * @throws Exception
     */
    public function unequipItem(int $inventorySlotId): array {
        if ($this->character->isInventoryFull()) {

            return $this->errorResult('Your inventory is full. Cannot unequip item. You have no room in your inventory.');
        }

        $foundItem = $this->character->inventory->slots->find($inventorySlotId);

        if (is_null($foundItem)) {
            return $this->errorResult('No item found to be unequipped.');
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        $character = $this->character->refresh();

        $this->updateCharacterAttackDataCache($character);

        event(new UpdateTopBarEvent($character->refresh()));

        return $this->successResult([
            'message' => 'Unequipped item: ' . $foundItem->item->name,
            'inventory' => [
                'inventory'         => $this->getInventoryForType('inventory'),
                'equipped'          => $this->getInventoryForType('equipped'),
                'sets'              => $this->getInventoryForType('sets')['sets'],
                'set_is_equipped'   => false,
                'set_name_equipped' => $this->getEquippedInventorySetName(),
                'usable_sets'       => $this->getUsableSets()
            ]
        ]);
    }

    /**
     * Unequip all items.
     *
     * @return array
     * @throws Exception
     */
    public function unequipAllItems(): array {
        if ($this->character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Cannot unequip items You have no room in your inventory.');
        }

        $this->character->inventory->slots->each(function ($slot) {
            $slot->update([
                'equipped' => false,
                'position' => null,
            ]);
        });

        $character = $this->character->refresh();

        $this->updateCharacterAttackDataCache($character);

        return $this->successResult([
            'message' => 'All items have been removed.',
            'inventory' => [
                'inventory'         => $this->getInventoryForType('inventory'),
                'equipped'          => $this->getInventoryForType('equipped'),
                'set_is_equipped'   => false,
                'set_name_equipped' => $this->getEquippedInventorySetName(),
                'sets'              => $this->getInventoryForType('sets')['sets'],
                'usable_sets'       => $this->getUsableSets()
            ]
        ]);
    }

    /**
     * Destroy Alchemy item.
     *
     * @param int $slotId
     * @return array
     */
    public function destroyAlchemyItem(int $slotId): array {
        $slot = $this->character->inventory->slots->filter(function ($slot) use ($slotId) {
            return $slot->id === $slotId;
        })->first();

        if (is_null($slot)) {

            return $this->errorResult('No item found.');
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'Destroyed Alchemy Item: ' . $name . '.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * Destroy all alchemy items.
     *
     * @return array
     */
    public function destroyAllAlchemyItems(): array {
        $slots = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'alchemy';
        });

        foreach ($slots as $slot) {
            $slot->delete();
        }

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'Destroyed All Alchemy Items.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * Updates the character stats.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    protected function updateCharacterAttackDataCache(Character $character): void {
        $this->updateCharacterAttackTypesHandler->updateCache($character);
    }

    /**
     * Fetch type based on accepted types.
     *
     * @param string $type
     * @return string
     * @throws Exception
     */
    protected function fetchType(string $type): string {

        if (in_array($type, ArmourTypes::armourTypes())) {
            $type = 'armour';
        }

        $acceptedTypes = [
            'weapon', 'ring', 'shield', 'artifact', 'spell', 'armour',
            'trinket', 'stave', 'hammer', 'bow', 'fan', 'scratch-awl', 'gun', 'mace', 'alchemy', 'quest',
        ];

        // Spells do not have the tye spell - they are differentiated by damage or healing suffix.
        if ($type === 'spell-damage' || $type === 'spell-healing') {
            $type = 'spell';
        }

        return !in_array($type, $acceptedTypes) ? throw new Exception('Unknown Item type: ' . $type) : $type;
    }
}
