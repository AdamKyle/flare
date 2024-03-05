<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemUsabilityType;
use App\Game\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use function PHPUnit\Framework\isEmpty;

class UseItemService {

    use ResponseBuilder;

    const MAX_TIME = 8 * 60;
    const MAX_AMOUNT = 10;

    /**
     * @var CharacterSheetBaseInfoTransformer $characterAttackTransformer
     */
    private CharacterSheetBaseInfoTransformer $characterAttackTransformer;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    /**
     * @var CharacterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterAttackTransformer
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     * @param CharacterInventoryService $characterInventoryService
     */
    public function __construct(Manager $manager,
                                CharacterSheetBaseInfoTransformer $characterAttackTransformer,
                                UpdateCharacterAttackTypes $updateCharacterAttackTypes,
                                CharacterInventoryService $characterInventoryService,
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
        $this->characterInventoryService  = $characterInventoryService;
    }

    public function useManyItemsFromInventory(Character $character, array $itemsToUse): array {
        $currentBoonCount = $character->boons->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        $removedSomeItems = false;
        $possibleNewBoonCount = $currentBoonCount + count($itemsToUse);
        $itemsToRemove = max(0, $possibleNewBoonCount - self::MAX_AMOUNT);

        if ($possibleNewBoonCount > self::MAX_AMOUNT) {
            $itemsToUse = array_slice($itemsToUse, 0, -$itemsToRemove);
            $removedSomeItems = true;
        }

        $slots = $character->inventory->slots()->whereIn('id', $itemsToUse)->get();

        if ($slots->isEmpty()) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        foreach ($slots as $slot) {
            if (!$this->useItem($slot, $character)) {
                $removedSomeItems = true;
            };

            $character = $character->refresh();
        }

        $this->updateCharacterAttackTypes->updateCache($character);
        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message' => 'Used selected items.' . ($removedSomeItems ? ' Some items were not able to be used because of the amount of boons you have. You can check your usable items section to see which ones are left.' : ''),
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * @param Character $character
     * @param Item $item
     * @return array
     * @throws Exception
     */
    public function useSingleItemFromInventory(Character $character, Item $item): array
    {
        $currentBoonCount = $character->boons->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        $foundSlot = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        if (is_null($foundSlot)) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        if (!$this->useItem($foundSlot, $character)) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours.'
            );
        };

        $this->updateCharacterAttackTypes->updateCache($character);
        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message' => 'Used selected item.',
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * Use the item on the character and create a boon.
     *
     * @param InventorySlot $slot
     * @param Character $character
     * @return bool
     */
    public function useItem(InventorySlot $slot, Character $character): bool {
        $foundBoon = $character->boons()
            ->where('item_id', $slot->item_id)
            ->where('last_for_minutes', '<=', self::MAX_TIME) // corrected where clause
            ->orderBy('created_at', 'desc') // Order by creation time to ensure we check the latest boon first
            ->first();

        if (!is_null($foundBoon) && $slot->item->can_stack && $foundBoon->amount_used <= self::MAX_AMOUNT) {
            $newLastsForMinutes = $foundBoon->last_for_minutes + $slot->item->lasts_for;

            if ($newLastsForMinutes > self::MAX_TIME) {
                return false;
            }

            $timeStamp = $foundBoon->complete->addMinutes($slot->item->lasts_for);
            $amountUsed = min(self::MAX_AMOUNT, $foundBoon->amount_used + 1);

            $foundBoon->update([
                'last_for_minutes' => min(self::MAX_TIME, $foundBoon->last_for_minutes + $slot->item->lasts_for),
                'complete' => $timeStamp,
                'amount_used' => $amountUsed,
            ]);

            $slot->delete();

            return true;
        }

        $completedAt = now()->addMinutes($slot->item->lasts_for);

        $boon = $character->boons()->create([
            'character_id'      => $character->id,
            'item_id'           => $slot->item->id,
            'started'           => now(),
            'complete'          => $completedAt,
            'amount_used'       => 1,
            'last_for_minutes'  => $slot->item->lasts_for,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $slot->delete();

        return true;
    }


    /**
     * Removes a boon from the character and updates their info.
     *
     * @param Character $character
     * @param CharacterBoon $boon
     */
    public function removeBoon(Character $character, CharacterBoon $boon) {
        $boon->delete();

        $character = $character->refresh();

        $this->updateCharacter($character);
    }

    /**
     * Update a character based on the item they used.
     *
     * @param Character $character
     * @param Item|null $item
     * @return void
     */
    public function updateCharacter(Character $character, Item $item = null) {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character->refresh());

        $characterAttack = new ResourceItem($character, $this->characterAttackTransformer);

        event(new UpdateBaseCharacterInformation($character->user, $this->manager->createData($characterAttack)->toArray()));
        event(new UpdateTopBarEvent($character));

        if (!is_null($item)) {
            event(new ServerMessageEvent($character->user, 'You used: ' . $item->name));
        }

        $boons = $character->boons->toArray();

        foreach ($boons as $key => $boon) {
            $item   = Item::find($boon['item_id']);

            if (is_null($item->affects_skill_type)) {
                continue;
            }

            $skills = GameSkill::where('type', $item->affect_skill_type)->pluck('name')->toArray();

            $boon['affected_skills'] = implode(', ', $skills);

            $boons[$key] = $boon;
        }

        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $boons));
    }
}
