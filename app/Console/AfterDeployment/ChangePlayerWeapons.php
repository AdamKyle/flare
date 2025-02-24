<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterInventory\Values\ItemType;
use Exception;
use Illuminate\Console\Command;

class ChangePlayerWeapons extends Command
{

    const INVALID_TYPE = 'weapon';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:player-weapons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Changes player weapons';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                $character = $this->swapWeaponsInInventory($character);
                $character = $this->swapWeaponsInEquippableSets($character);

                $this->cleanUpInvalidItemsFromNonEquippableSets($character);
            }
        });
    }

    private  function cleanUpInvalidItemsFromNonEquippableSets(Character $character): void {
        $characterNonEquippableSets = $character->inventorySets()->where('can_be_equipped', false)->get();

        foreach ($characterNonEquippableSets as $characterNonEquippableSet) {
            if ($characterNonEquippableSet->slots->isEmpty()) {
                continue;
            }

            $invalidWeaponTypesInNonEquippableSlots = $characterNonEquippableSet->slots->filter(function ($slot) {
                return $slot->item->type === self::INVALID_TYPE;
            });

            if ($invalidWeaponTypesInNonEquippableSlots->isEmpty()) {
                continue;
            }

            foreach ($invalidWeaponTypesInNonEquippableSlots as $slot) {
                $slot->delete();
            }
        }
    }

    private function swapWeaponsInEquippableSets(Character $character): Character {
        $characterEquippableSets = $character->inventorySets()->where('can_be_equipped', true)->get();

        $properTypeForCharacter = $this->getProperTypeForCharacter($character);

        foreach ($characterEquippableSets as $characterEquippableSet) {
            if ($characterEquippableSet->slots->isEmpty()) {
                continue;
            }

            $invalidWeaponTypesInEquippableSlots = $characterEquippableSet->slots->filter(function ($slot) {
                return $slot->item->type === self::INVALID_TYPE;
            });

            if ($invalidWeaponTypesInEquippableSlots->isEmpty()) {
                continue;
            }

            foreach ($invalidWeaponTypesInEquippableSlots as $slot) {
                // The character is a class which doesn't use weapons
                if (is_null($properTypeForCharacter)) {
                    $slot->delete();

                    continue;
                }

                $oldItem = $slot->item;
                $newItem = $this->fetchNewItem($oldItem, $properTypeForCharacter);

                $newItem = $this->moveFromOldItemToNewItem($oldItem, $newItem);

                $slot->update([
                    'item_id' => $newItem->id,
                ]);
            }
        }

        return $character->refresh();
    }

    private function swapWeaponsInInventory(Character $character): Character {
        $invalidWeaponTypesInInventorySlots = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === self::INVALID_TYPE;
        });

        if ($invalidWeaponTypesInInventorySlots->isEmpty()) {
            return  $character;
        }

        $properTypeForCharacter = $this->getProperTypeForCharacter($character);

        foreach ($invalidWeaponTypesInInventorySlots as $slot) {

            // The character is a class which doesn't use weapons
            if (is_null($properTypeForCharacter)) {
                $slot->delete();

                continue;
            }

            $oldItem = $slot->item;
            $newItem = $this->fetchNewItem($oldItem, $properTypeForCharacter);

            $newItem = $this->moveFromOldItemToNewItem($oldItem, $newItem);

            $slot->update([
                'item_id' => $newItem->id,
            ]);
        }

        return $character->refresh();
    }

    private function getProperTypeForCharacter(Character $character): ItemType|null {
        $properTypeForCharacter = ItemTypeMapping::getForClass($character->class->name);

        if (is_array($properTypeForCharacter)) {
            $properTypeForCharacter = $properTypeForCharacter[rand(0, count($properTypeForCharacter) - 1)];
        }

        return $properTypeForCharacter;
    }

    private function fetchNewItem(Item $oldItem, ItemType $properTypeForCharacter): Item {
        $item = Item::where('type', $properTypeForCharacter->value)
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id');


        if (!is_null($oldItem->specialty_type)) {
            return $item->where('specialty_type', $oldItem->specialty_type)->first();
        }

        return $item->whereNull('specialty_type')
                    ->where('skill_level_required', '<=', $oldItem->skill_level_required)
                    ->orderByDesc('skill_level_required')->first();
    }

    private function moveFromOldItemToNewItem(Item $oldItem, Item $newItem): Item {


        $newItem->update([
            'item_prefix_id' => $oldItem->item_prefix_id,
            'item_suffix_id' => $newItem->item_suffix_id,
            'is_mythic' => $oldItem->is_mythic,
            'is_cosmic' => $oldItem->is_cosmic,
            'market_sellable' => $oldItem->market_sellable,
        ]);

        $newItem = $this->applyHolyStacks($oldItem, $newItem);

        return $this->applyGems($oldItem, $newItem);
    }

    /**
     * Apply holy stacks from the old item to the new one.
     */
    private function applyHolyStacks(Item $oldItem, Item $newItem): Item
    {

        if ($oldItem->appliedHolyStacks()->count() > 0) {

            foreach ($oldItem->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $newItem->id;

                $newItem->appliedHolyStacks()->create($stackAttributes);

                $newItem = $newItem->refresh();
            }
        }

        return $newItem->refresh();
    }

    /**
     * Add gems.
     */
    private function applyGems(Item $oldItem, Item $newItem): Item
    {
        if ($oldItem->socket_count > 0) {
            foreach ($oldItem->sockets as $socket) {
                $newItem->sockets()->create([
                    'item_id' => $newItem->id,
                    'gem_id' => $socket->gem_id,
                ]);

                $newItem = $newItem->refresh();
            }

            $newItem->update([
                'socket_count' => $oldItem->socket_count,
            ]);

            return $newItem->refresh();
        }

        return $newItem;
    }

}
