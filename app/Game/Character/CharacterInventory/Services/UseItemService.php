<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\AutomationType;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UseItemService
{
    use ResponseBuilder;

    const MAX_TIME = 8 * 60;

    const MAX_AMOUNT = 10;

    private CharacterSheetBaseInfoTransformer $characterAttackTransformer;

    private Manager $manager;

    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    private CharacterInventoryService $characterInventoryService;

    public function __construct(
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterAttackTransformer,
        UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        CharacterInventoryService $characterInventoryService,
    ) {
        $this->manager = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
        $this->characterInventoryService = $characterInventoryService;
    }

    public function useManyItemsFromInventory(Character $character, array $itemsToUse): array
    {
        $automation = $this->activeAutomation($character);

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

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

        if (! is_null($automation) && $slots->contains(function (InventorySlot $slot): bool {
            return ! $this->isAlchemyBoonItem($slot->item);
        })) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        foreach ($slots as $slot) {
            if (! $this->useItem($slot, $character)) {
                $removedSomeItems = true;
            }

            $character = $character->refresh();
        }

        $this->updateCharacterAttackTypes->updateCache($character);
        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastCharacterBoons($character);

        $inventory = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message' => 'Used selected items.' . ($removedSomeItems ? ' Some items were not able to be used because of the amount of boons you have. You can check your usable items section to see which ones are left.' : ''),
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function useSingleItemFromInventory(Character $character, Item $item): array
    {
        $automation = $this->activeAutomation($character);

        if (! is_null($automation) && ! $this->isAlchemyBoonItem($item)) {
            return $this->errorResult($this->automationItemUseMessage($automation));
        }

        $currentBoonCount = $character->boons()->active()->sum('amount_used');

        if ($currentBoonCount >= self::MAX_AMOUNT) {
            return $this->errorResult('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        $foundSlot = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        if (is_null($foundSlot)) {
            return $this->errorResult('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?');
        }

        if (! $this->useItem($foundSlot, $character)) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        $this->updateCharacterAttackTypes->updateCache($character);
        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastCharacterBoons($character);

        $inventory = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message' => 'Used selected item.',
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Use the item on the character and create a boon.
     */
    public function useItem(InventorySlot $slot, Character $character): bool
    {
        $foundBoon = $character->boons()
            ->active()
            ->where('item_id', $slot->item_id)
            ->where('last_for_minutes', '<=', self::MAX_TIME)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! is_null($foundBoon)) {
            if (! $slot->item->can_stack) {
                return false;
            }

            if ($foundBoon->amount_used >= self::MAX_AMOUNT) {
                return false;
            }

            $minutesLeft = $foundBoon->complete->lessThanOrEqualTo(now()) ? 0 : (int) ceil(now()->diffInSeconds($foundBoon->complete) / 60);
            $newLastsForMinutes = min(self::MAX_TIME, $minutesLeft + $slot->item->lasts_for);

            if ($newLastsForMinutes <= $minutesLeft) {
                return false;
            }

            $timeStamp = now()->addMinutes($newLastsForMinutes);
            $amountUsed = min(self::MAX_AMOUNT, $foundBoon->amount_used + 1);

            $foundBoon->update([
                'last_for_minutes' => $newLastsForMinutes,
                'complete' => $timeStamp,
                'amount_used' => $amountUsed,
            ]);

            $slot->delete();

            return true;
        }

        $completedAt = now()->addMinutes($slot->item->lasts_for);

        $boon = $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $slot->item->id,
            'started' => now(),
            'complete' => $completedAt,
            'amount_used' => 1,
            'last_for_minutes' => $slot->item->lasts_for,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $slot->delete();

        return true;
    }

    public function fillUpBoon(Character $character, CharacterBoon $boon): array
    {
        $boon = $character->boons()->active()->find($boon->id);

        if (is_null($boon)) {
            return $this->errorResult('This boon is no longer active.');
        }

        $slots = $character->inventory->slots()
            ->where('item_id', $boon->item_id)
            ->get();

        if ($slots->isEmpty()) {
            return $this->errorResult('You do not have any more of that item.');
        }

        $item = Item::find($boon->item_id);
        $minutesLeft = $boon->complete->lessThanOrEqualTo(now()) ? 0 : (int) ceil(now()->diffInSeconds($boon->complete) / 60);
        $missing = self::MAX_TIME - $minutesLeft;

        if ($missing <= 0) {
            return $this->errorResult(
                'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.'
            );
        }

        $needed = (int) ceil($missing / $item->lasts_for);
        $available = $slots->count();
        $used = min($needed, $available);
        $timeAdded = min($missing, $used * $item->lasts_for);

        $slots->take($used)->each(function (InventorySlot $slot): void {
            $slot->delete();
        });

        $boon->update([
            'complete' => $boon->complete->addMinutes($timeAdded),
            'last_for_minutes' => $minutesLeft + $timeAdded,
            'amount_used' => min((int) ceil(self::MAX_TIME / $item->lasts_for), $boon->amount_used + $used),
        ]);

        return $this->successResult([
            'message' => $item->name . ' filled up using ' . $used . ' item(s), adding ' . $timeAdded . ' minutes.',
            'boons' => $character->boons()->active()->get(),
        ]);
    }

    private function activeAutomation(Character $character): ?CharacterAutomation
    {
        return $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    private function isAlchemyBoonItem(Item $item): bool
    {
        return $item->type === 'alchemy'
            && $item->usable
            && $item->lasts_for > 0
            && ! $item->damages_kingdoms
            && ! $item->can_use_on_other_items;
    }

    private function automationItemUseMessage(CharacterAutomation $automation): string
    {
        return 'No you are busy, you can use Alchemy items that apply boons to your character. Please cancel your: ' . $this->automationName($automation) . ', if you want to use this.';
    }

    private function automationName(CharacterAutomation $automation): string
    {
        $automationType = new AutomationType($automation->type);

        if ($automationType->isExploring()) {
            return 'Exploration';
        }

        if ($automationType->isDelve()) {
            return 'Delve';
        }

        return 'Faction Loyalty';
    }

    private function broadcastCharacterBoons(Character $character): void
    {
        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $character->boons()->active()->get()->toArray()));
    }

    /**
     * Removes a boon from the character and updates their info.
     */
    public function removeBoon(Character $character, CharacterBoon $boon)
    {
        $boon->delete();

        $character = $character->refresh();

        $this->updateCharacter($character);
    }

    /**
     * Update a character based on the item they used.
     *
     * @return void
     */
    public function updateCharacter(Character $character, ?Item $item = null)
    {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character->refresh());

        $characterAttack = new ResourceItem($character, $this->characterAttackTransformer);

        event(new UpdateBaseCharacterInformation($character->user, $this->manager->createData($characterAttack)->toArray()));
        event(new UpdateTopBarEvent($character));

        if (! is_null($item)) {
            event(new ServerMessageEvent($character->user, 'You used: ' . $item->name));
        }

        $boons = $character->boons()->active()->get()->toArray();

        foreach ($boons as $key => $boon) {
            $item = Item::find($boon['item_id']);

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
