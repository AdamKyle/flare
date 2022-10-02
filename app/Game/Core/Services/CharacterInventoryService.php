<?php

namespace App\Game\Core\Services;

use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Skills\Jobs\DisenchantItem;
use League\Fractal\Resource\Collection as LeagueCollection;
use League\Fractal\Resource\Item as LeagueItem;
use App\Flare\Models\Inventory;
use App\Flare\Models\SetSlot;
use App\Flare\Transformers\InventoryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use League\Fractal\Manager;

class CharacterInventoryService {

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
     * @var InventoryTransformer $inventoryTransformer
     */
    private InventoryTransformer $inventoryTransformer;

    /**
     * @var UsableItemTransformer $usableItemTransformer
     */
    private UsableItemTransformer $usableItemTransformer;

    /**
     * @var Manager $manager
     */
    private $manager;

    public function __construct(InventoryTransformer $inventoryTransformer, UsableItemTransformer $usableItemTransformer,  Manager $manager) {
        $this->inventoryTransformer  = $inventoryTransformer;
        $this->usableItemTransformer = $usableItemTransformer;
        $this->manager               = $manager;
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
        switch($type) {
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
                    'set_equipped' => $this->isInventorySetIsEquipped
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
            $slot = SetSlot::where('item_id', $item->id)->first();

            $characterInventorySet = InventorySet::find($slot->inventory_set_id)->where('character_id', $character->id)->first();

            if (is_null($characterInventorySet)) {
                $slot = null;
            }
        }

        return $slot;
    }

    /**
     * Disenchant all items in an inventory.
     *
     * @param Collection $slots
     * @param Character $character
     * @return void
     */
    public function disenchantAllItems(Collection $slots, Character $character) {
        $jobs = [];

        foreach ($slots as $index => $slot) {
            if ($index !== 0) {
                if ($index === ($slots->count() - 1)) {
                    $jobs[] = new DisenchantItem($character, $slot->id, true);
                } else {
                    $jobs[] = new DisenchantItem($character, $slot->id);
                }
            }
        }

        DisenchantItem::withChain($jobs)->onConnection('disenchanting')->dispatch($character, $slots->first()->id);
    }

    /**
     * Get character inventory sets.
     *
     * @return array
     */
    public function getCharacterInventorySets(): array {
        $sets = [];

        foreach($this->character->inventorySets as $index => $inventorySet) {

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

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
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

        $slots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
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

            if (!$inventorySet->equipped) {

                $indexes[] = [
                    'index'    => array_search($id, $setIds) + 1,
                    'id'       => $id,
                    'name'     => is_null($inventorySet->name) ? 'Set ' . array_search($id, $setIds) + 1 : $inventorySet->name,
                    'equipped' => $inventorySet->equipped,
                ];
            }
        }

        return $indexes;
    }

    public function getInventoryCollection(): Collection {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->whereNotIn('items.type', ['quest', 'alchemy']);
        })->where('inventory_slots.equipped', false)->select('inventory_slots.*')->get();
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

    public function findCharacterInventorySlotIds(): array {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->whereNotIn('items.type', ['quest', 'alchemy']);
        })->where('inventory_slots.equipped', false)->select('inventory_slots.*')->orderBy('inventory_slots.id', 'asc')->pluck('inventory_slots.id')->toArray();
    }

    /**
     * Fetch equipped items.
     *
     * @return array|null
     */
    public function fetchEquipped(): array|null {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots     = InventorySlot::where('inventory_id', $inventory->id)->where('equipped', true)->get();

        if ($slots->isNotEmpty()) {
            $slots = new LeagueCollection($slots, $this->inventoryTransformer);

            return $this->manager->createData($slots)->toArray();
        }

        $inventorySet = InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->first();

        if (is_null($inventorySet)) {
            return null;
        }

        $this->isInventorySetIsEquipped = true;

        if (!is_null($inventorySet->name)) {
            $this->inventorySetEquippedName = $inventorySet->name;
        } else {
            $index = $this->character->inventorySets->search(function ($set) use($inventorySet) {
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
     * @param Request $request
     * @return CharacterInventoryService
     */
    public function setInventory(string $type): CharacterInventoryService {

        $useArray = !in_array($type, ['body','shield','leggings','feet','sleeves','helmet','gloves']);

        $this->inventory = $this->getInventory($type, $useArray);

        return $this;
    }

    protected function getInventory(string $type, bool $useArray = false) {
        $inventory = $this->character->inventory->slots->filter(function($slot) use($type, $useArray) {
            if ($useArray) {
                return in_array($slot->position, $this->positions) && $slot->equipped;
            }

            return $slot->item->type === $type && $slot->equipped;
        });

        if ($inventory->isEmpty()) {
            $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();
            if (!is_null($equippedSet)) {
                $inventory = $equippedSet->slots->filter(function($slot) use($type, $useArray) {
                    if ($useArray) {
                        return in_array($slot->position, $this->positions) && $slot->equipped;
                    }

                    return $slot->item->type === $type && $slot->equipped;
                });
            }
        }

        return $inventory;
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
     * @param Request $request
     * @param Item $item
     * @return string
     */
    public function getType(Item $item, string $type = null): string {
        if (!is_null($type)) {
            return $this->fetchType($type);
        }

        if ($item->type === 'bow') {
            return $item->type;
        }

        return $item->crafting_type;
    }

    protected function fetchType(string $type): string {
        $acceptedTypes = [
            'weapon', 'ring', 'shield', 'artifact', 'spell', 'armour', 'trinket', 'stave', 'hammer'
        ];

        return in_array($type, $acceptedTypes) ? $type : 'armour';
    }
}
