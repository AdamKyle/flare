<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Exception;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Tests\Traits\CreateInventorySets;

class InventorySetManagement
{
    use CreateInventorySets;

    /**
     * @var BuildCharacterAttackTypes $buildCharacterAttackData
     */
    private BuildCharacterAttackTypes $buildCharacterAttackData;

    /**
     * @var array $inventorySetIds
     */
    private array $inventorySetIds = [];

    /**
     * @var int $inventoryId
     */
    private int $inventoryId;

    /**
     * @param Character $character
     * @param CharacterFactory|null $characterFactory
     */
    public function __construct(private Character $character, private readonly ?CharacterFactory $characterFactory = null)
    {
        $this->buildCharacterAttackData = resolve(BuildCharacterAttackTypes::class);
        $this->inventoryId = $character->inventory->id;
    }

    /**
     * Get the character factory.
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Assign x inventory Sets.
     *
     * @return $this
     */
    public function createInventorySets(int $amount = 1, bool $useName = false): InventorySetManagement
    {
        $inventorySets = collect();

        for ($i = 1; $i <= $amount; $i++) {
            $inventorySets->push([
                'character_id' => $this->character->id,
                'name' => ($useName ? Str::random(12) : null),
            ]);
        }

        $inventorySetModel = $this->character->inventorySets()->getModel();

        $inventorySets->chunk(100)->each(function ($chunk) use ($inventorySetModel) {
            $inventorySetModel->newQuery()->insert($chunk->all());
        });

        $this->appendLatestInventorySetIds($amount);

        return $this;
    }

    /**
     * Puts an item in the characters inventory set.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function putItemInSet(Item $item, int $setIndex, ?string $position = null, bool $equipped = false): InventorySetManagement
    {
        $setId = $this->getInventorySetId($setIndex);

        $this->createInventorySetSlot([
            'inventory_set_id' => $setId,
            'item_id' => $item->id,
            'equipped' => $equipped,
            'position' => $position,
        ]);

        if ($equipped) {
            $this->character->inventorySets()->where('id', $setId)->update(['is_equipped' => true]);

            InventorySlot::query()
                ->where('inventory_id', $this->inventoryId)
                ->where('equipped', true)
                ->update(['equipped' => false]);

            $this->buildCharacterAttackData->buildCache($this->character->refresh());
        }

        return $this;
    }

    /**
     * Get the character.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Gets the ID or throws an exception.
     *
     * @throws Exception
     */
    public function getInventorySetId(int $index): int
    {
        if (isset($this->inventorySetIds[$index])) {
            return $this->inventorySetIds[$index];
        }

        throw new Exception('Index does not exist for inventory sets on this character.');
    }

    /**
     * @param int $amount
     * @return void
     */
    private function appendLatestInventorySetIds(int $amount): void
    {
        $latestIds = $this->character->inventorySets()
            ->orderByDesc('id')
            ->limit($amount)
            ->pluck('id')
            ->reverse()
            ->values()
            ->all();

        foreach ($latestIds as $latestId) {
            $this->inventorySetIds[] = $latestId;
        }
    }
}
