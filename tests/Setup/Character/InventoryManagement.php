<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;

class InventoryManagement
{
    private BuildCharacterAttackTypes $buildAttackData;

    public array $slotIds = [];

    public function __construct(private Character $character, private readonly ?CharacterFactory $characterFactory = null)
    {
        $this->buildAttackData = resolve(BuildCharacterAttackTypes::class);
    }

    /**
     * Equip the left hand with an item that isn't already equipped.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function equipLeftHand(string $itemName): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip an artifact.
     *
     * @param  string  $position  | artifact-one, artifact-two
     *
     * @throws Exception|InvalidArgumentException
     */
    public function equipArtifact(string $itemName, string $position = 'artifact-one'): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Equip an item.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function equipItem(string $position, string $itemName): InventoryManagement
    {
        $slot = $this->fetchSlot($itemName);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character = $this->character->refresh();

        $this->buildAttackData->buildCache($this->character);

        return $this;
    }

    /**
     * Give item to the character.
     *
     * Ignores the inventory max limit.
     */
    public function giveItem(Item $item, bool $equip = false, ?string $position = null): InventoryManagement
    {
        $this->slotIds[] = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => $equip,
            'position' => $position,
        ])->id;

        return $this;
    }

    /**
     * Give the same item multiple times.
     *
     * @return $this
     */
    public function giveItemMultipleTimes(Item $item, int $amount = 1, bool $equip = false, ?string $position = null): InventoryManagement
    {
        for ($i = 1; $i <= $amount; $i++) {
            $this->slotIds[] = $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id' => $item->id,
                'equipped' => $equip,
                'position' => $position,
            ])->id;
        }

        return $this;
    }

    /**
     * Get the slot id of an item given to th character.
     *
     * You must first use the giveItem command, before calling this.
     * The index param is the index of the array starting at 0.
     */
    public function getSlotId(int $index): ?int
    {
        if (isset($this->slotIds[$index])) {
            return $this->slotIds[$index];
        }

        return null;
    }

    public function getSlotIds(): array
    {
        return $this->slotIds;
    }

    /**
     * Get the character factory.
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    private function fetchSlot(string $itemName): InventorySlot
    {
        $slot = InventorySlot::query()
            ->where('inventory_id', $this->character->inventory->id)
            ->where('equipped', false)
            ->whereHas('item', function ($query) use ($itemName) {
                $query->where('name', $itemName);
            })
            ->first();

        if (is_null($slot)) {
            throw new Exception('Item is not in inventory or is already equipped');
        }

        return $slot;
    }
}
