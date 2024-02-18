<?php

namespace App\Game\CharacterInventory\Services;

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
use App\Game\Messages\Events\ServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UseItemService {

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
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterAttackTransformer
     */
    public function __construct(Manager $manager, CharacterSheetBaseInfoTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    /**
     * Use the item on the character and create a boon.
     *
     * @param InventorySlot $slot
     * @param Character $character
     * @return void
     */
    public function useItem(InventorySlot $slot, Character $character) {

        $foundBoon = $character->boons()
            ->where('item_id', $slot->item_id)
            ->where('last_for_minutes', '<', self::MAX_TIME)
            ->where(function ($query) {
                $query->where('amount_used', '<', self::MAX_AMOUNT);
            })
            ->first();

        if (!is_null($foundBoon) && $slot->item->can_stack) {
            $completesAtMinutes = $foundBoon->complete->diffInMinutes();

            $remainingTime = min(self::MAX_TIME - $completesAtMinutes, $completesAtMinutes);

            $timeStamp = now();

            $amountUsed = min(self::MAX_AMOUNT, $foundBoon->amount_used + 1);

            $timeStamp->addMinutes($remainingTime);

            $lastsFor = $slot->item->lasts_for;

            $additionalTime = min($lastsFor, self::MAX_TIME - $timeStamp->diffInMinutes(now()));

            $timeStamp->addMinutes($additionalTime);

            $foundBoon->update([
                'last_for_minutes' => min(self::MAX_TIME, $foundBoon->last_for_minutes + $slot->item->lasts_for),
                'complete' => $timeStamp,
                'amount_used' => $amountUsed,
            ]);

            $slot->delete();

            return;
        }

        $completedAt = now()->addMinutes($slot->item->lasts_for);

        $boon = $character->boons()->create([
            'character_id'             => $character->id,
            'item_id'                  => $slot->item->id,
            'started'                  => now(),
            'complete'                 => $completedAt,
            'amount_used'              => 1,
            'last_for_minutes'         => $slot->item->lasts_for,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $slot->delete();
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
