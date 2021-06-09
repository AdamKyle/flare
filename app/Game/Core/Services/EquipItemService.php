<?php

namespace App\Game\Core\Services;

use App\Game\Core\Events\UpdateAttackStats;
use League\Fractal\Manager;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Resource\Item as ResourceItem;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Exceptions\EquipItemException;


class EquipItemService {

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterAttackTransformer $characterTransformer
     */
    private $characterTransformer;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * EquipItemService constructor.
     *
     * @param Manager $manager
     * @param CharacterAttackTransformer $characterTransformer
     */
    public function __construct(Manager $manager, CharacterAttackTransformer $characterTransformer) {
        $this->manager              = $manager;
        $this->characterTransformer = $characterTransformer;
    }

    /**
     * Set the request
     *
     * @param Request $request
     * @return EquipItemService
     */
    public function setRequest(Request $request): EquipItemService {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the character
     *
     * @param Charactr $character
     * @return EquipItemService
     */
    public function setCharacter(Character $character): EquipItemService {
        $this->character = $character;

        return $this;
    }

    /**
     * Equip the item
     *
     * @return Item
     */
    public function equipItem(): Item {

        $characterSlot = $this->character->inventory->slots->filter(function($slot) {
            return $slot->id === (int) $this->request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($characterSlot)) {
            throw new EquipItemException('Could not equip item because you either do not have it, or it is equipped already.');
        }

        $itemForPosition = $this->character->inventory->slots->filter(function($slot) {
            return $slot->position === $this->request->position && $slot->equipped;
        })->first();

        if (!is_null($itemForPosition)) {
            $itemForPosition->update(['equipped' => false]);
        }

        $characterSlot->update([
            'equipped' => true,
            'position' => $this->request->position,
        ]);

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character, $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        return $characterSlot->item;
    }

    /**
     * Get Item stats
     *
     * @param Item $toCompare
     * @param Collection $inventorySlots
     * @return array
     */
    public function getItemStats(Item $toCompare, Collection $inventorySlots): array {
       return resolve(ItemComparison::class)->fetchDetails($toCompare, $inventorySlots);
    }
}
