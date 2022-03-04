<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Jobs\UseMultipleItems;
use App\Game\Core\Requests\RemoveItemRequest;
use App\Game\Core\Requests\RenameSetRequest;
use App\Game\Core\Requests\SaveEquipmentAsSet;
use App\Game\Core\Requests\UseManyItemsValidation;
use App\Game\Core\Services\UseItemService;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\EnchantingService;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Models\Character;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Jobs\DisenchantItem;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Requests\MoveItemRequest;
use App\Game\Core\Services\InventorySetService;

class CharacterInventoryController extends Controller {

    private $characterInventoryService;

    private $characterTransformer;

    private $buildCharacterAttackTypes;

    private $enchantingService;

    private $manager;

    public function __construct(CharacterInventoryService $characterInventoryService,
                                CharacterSheetBaseInfoTransformer $characterTransformer,
                                BuildCharacterAttackTypes $buildCharacterAttackTypes,
                                EnchantingService $enchantingService,
                                Manager $manager) {

        $this->characterInventoryService = $characterInventoryService;
        $this->characterTransformer      = $characterTransformer;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->enchantingService         = $enchantingService;
        $this->manager                   = $manager;
    }

    public function inventory(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi(), 200);
    }


    public function destroy(Request $request, Character $character) {

        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t own that item.'], 422);
        }

        if ($slot->equipped) {
            return response()->json(['message' => 'Cannot destroy equipped item.'], 422);
        }

        $name = $slot ->item->affix_name;

        $slot->delete();

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json(['message' => 'Destroyed ' . $name . '.'], 200);
    }

    public function destroyAll(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slotIds   = $inventory->fetchCharacterInventory()->pluck('id');

        $character->inventory->slots()->whereIn('id', $slotIds)->delete();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        return response()->json(['message' => 'Destroyed All Items.'], 200);
    }

    public function disenchantAll(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slots   = $inventory->fetchCharacterInventory()->filter(function($slot) {
            return (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id));
        })->values();

        if ($slots->isNotEmpty()) {
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

            return response()->json(['message' => 'You can freely move about.
                Your inventory will update as items disenchant. Check chat to see
                the total gold dust earned.'
            ]);
        }

        return response()->json(['message' => 'You have nothing to disenchant.']);
    }

    public function moveToSet(MoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot         = $character->inventory->slots()->find($request->slot_id);
        $inventorySet = $character->inventorySets()->find($request->move_to_set);

        if (is_null($slot) || is_null($inventorySet)) {
            return response()->json([
                'message' => 'Either the slot or the inventory set does not exist.'
            ], 422);
        }

        $itemName = $slot->item->affix_name;

        $inventorySetService->assignItemToSet($inventorySet, $slot);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        if (is_null($inventorySet->name)) {
            $index     = $character->inventorySets->search(function($set) use ($request) {
                return $set->id === $request->move_to_set;
            });

            return response()->json([
                'message' => $itemName . ' Has been moved to: Set ' . $index + 1,
            ]);
        }

        return response()->json([
            'message' => $itemName . ' Has been moved to: ' . $inventorySet->name,
        ]);
    }

    public function renameSet(RenameSetRequest $request, Character $character) {
        $inventorySet = $character->inventorySets()->find($request->set_id);

        if (is_null($inventorySet)) {
            return response()->json([
                'message' => 'Set does not exist.'
            ], 422);
        }

        $inventorySet->update([
            'name' => $request->set_name
        ]);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json([
            'message' => 'Renamed set to: ' . $request->set_name,
        ]);
    }

    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character, InventorySetService $inventorySetService) {
        $currentlyEquipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        });

        $inventorySet = $character->inventorySets()->find($request->move_to_set);

        foreach ($currentlyEquipped as $equipped) {
            $inventorySet->slots()->create(array_merge(['inventory_set_id' => $inventorySet->id], $equipped->getAttributes()));

            $equipped->delete();
        }

        $inventorySet->update([
            'is_equipped' => true,
        ]);

        $character = $character->refresh();

        $setIndex = $character->inventorySets->search(function($set) {
            return $set->is_equipped;
        });

        event(new UpdateTopBarEvent($character));

        $this->updateCharacterAttackDataCache($character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        return response()->json(['message' => 'Set ' . $setIndex + 1 . ' is now equipped (equipment has been moved to the set)']);
    }

    public function removeFromSet(RemoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot = $character->inventorySets()->find($request->inventory_set_id)->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Either the slot or the inventory set does not exist.'], 422);
        }

        if ($slot->inventorySet->is_equipped) {
            return response()->json(['message' => 'You cannot move an equipped item into your inventory from this set. Unequip it first.'], 422);
        }

        $itemName = $slot->item->affix_name;

        $result = $inventorySetService->removeItemFromInventorySet($slot->inventorySet, $slot->item);

        // Chances are no inventory space.
        if ($result['status'] !== 200) {
            return response()->json(['message' => $result['message']], 422);
        }

        $character = $character->refresh();

        $index     = $character->inventorySets->search(function($set) use ($request) {
            return $set->id === $request->inventory_set_id;
        });

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        return response()->json(['message' => $itemName . ' Has been removed from Set ' . $index + 1 . ' and placed back into your inventory.'], 200);
    }

    public function emptySet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService) {
        $currentInventoryAmount    = $character->inventory_max - $inventorySet->slots->count();
        $originalInventorySetCount = $inventorySet->slots->count();
        $itemsRemoved              = 0;

        // Only grab the amount of items your inventory can hold.
        foreach ($inventorySet->slots()->take($currentInventoryAmount)->get() as $slot) {

            if ($currentInventoryAmount !== 0) {
                if ($inventorySetService->removeItemFromInventorySet($inventorySet, $slot->item)) {
                    $currentInventoryAmount -= 1;
                    $itemsRemoved           += 1;

                    continue;
                }

                // @codeCoverageIgnoreStart
                break;
                // @codeCoverageIgnoreEnd
            }
        }

        $setIndex = $character->inventorySets->search(function($set) use ($inventorySet) {
            return $set->id === $inventorySet->id;
        });

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        return response()->json(['message' => 'Removed ' . $itemsRemoved . ' of ' . $originalInventorySetCount . ' items from Set ' . $setIndex + 1], 200);
    }

    public function unequipItem(Request $request, Character $character, InventorySetService $inventorySetService) {
        if ($request->inventory_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
            $inventoryIndex = $character->inventorySets->search(function($set) { return $set->is_equipped; });

            $inventorySetService->unEquipInventorySet($inventorySet);

            $this->updateCharacterAttackDataCache($character);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));
            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));

            event(new CharacterInventoryDetailsUpdate($character->user));

            return response()->json(['message' => 'Unequipped Set ' . $inventoryIndex + 1 . '.'], 200);
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return response()->json(['error' => 'No item found to be equipped.'], 422);
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        $this->updateCharacterAttackDataCache($character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        return response()->json(['message' => 'Unequipped item.'], 200);
    }

    public function unequipAll(Request $request, Character $character, InventorySetService $inventorySetService) {

        if ($request->is_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();

            $inventorySetService->unEquipInventorySet($inventorySet);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));
        } else {
            $character->inventory->slots->each(function($slot) {
                $slot->update([
                    'equipped' => false,
                    'position' => null,
                ]);
            });
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character->refresh()));

        $this->updateCharacterAttackDataCache($character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        $affixData = $this->enchantingService->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        return response()->json(['message' => 'All items have been removed.'], 200);
    }

    public function useItem(Character $character, Item $item, UseItemService $useItemService) {
        if ($character->boons->count() === 10) {
            return response()->json(['message' => 'You can only have a max of ten boons applied.
            Check active boons to see which ones you have. You can always cancel one by clicking on the row.'], 422);
        }

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t have this item.'], 422);
        }

        $useItemService->useItem($slot, $character, $item);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'usable_items'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        $this->updateCharacterAttackDataCache($character);

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json(['message' => 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.'], 200);
    }

    public function equipItemSet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService) {
        if (!$inventorySet->can_be_equipped) {
            return response()->json(['message' => 'Set cannot be equipped.'], 422);
        }

        $inventorySetService->equipInventorySet($character, $inventorySet);

        $character->refresh();

        $setIndex = $character->inventorySets->search(function($set) {
            return $set->is_equipped;
        });

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->updateCharacterAttackDataCache($character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        return response()->json(['message' => 'Set ' . $setIndex + 1 . ' is now equipped']);
    }

    public function UseManyItems(UseManyItemsValidation $request, Character $character) {
        if ($character->boons->count() === 10) {
            return response()->json(['message' => 'You can only have a max of ten boons applied.
            Check active boons to see which ones you have. You can always cancel one by clicking on the row.'], 422);
        }

        $slots = $character->inventory->slots()->findMany($request->slot_ids);

        if ($slots->isEmpty()) {
            return response()->json(['message' => 'You do not own any of these items. What are you doing?'], 422);
        }

        $jobs = [];

        foreach ($slots as $index => $slot) {
            // @codeCoverageIgnoreStart
            if ($index !== 0) {
                $jobs[] = new UseMultipleItems($character, $slot->id);
            }
            // @codeCoverageIgnoreEnd
        }

        UseMultipleItems::withChain($jobs)->dispatch($character, $slots->first()->id);

        return response()->json(['message' => 'Boons are being applied. You can check Active Boons tab to see them be applied or check chat to see boons being applied.'], 200);
    }

    public function destroyAlchemyItem(Request $request, Character $character) {
        $slot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json([
                'message' => 'No item found.'
            ]);
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'usable_items'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json(['message' => 'Destroyed Alchemy Item: ' . $name . '.'], 200);
    }

    public function destroyAllAlchemyItems(Character $character) {
        $slots = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        });

        foreach ($slots as $slot) {
            $slot->delete();
        }

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'usable_items'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json(['message' => 'Destroyed All Alchemy Items.'], 200);
    }

    /**
     * Updates the character stats.
     *
     * @param Character $character
     * @return void
     */
    protected function updateCharacterAttackDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));

        event(new UpdateTopBarEvent($character));
    }
}
