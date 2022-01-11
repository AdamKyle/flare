<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Faction;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\CharacterXPService;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Jobs\AdventureItemDisenchantJob;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Values\FactionLevel;
use App\Game\Core\Values\FactionType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\DisenchantService;

class AdventureItemRewardService {

    use CanHaveQuestItem;

    private $inventorySetService;

    private $disenchantService;

    /**
     * @param CharacterService $characterService
     * @return void
     */
    public function __construct(InventorySetService $inventorySetService,
                                DisenchantService $disenchantService,
    ) {

        $this->inventorySetService       = $inventorySetService;
        $this->disenchantService         = $disenchantService;
    }

    public function handleItem(Item $item, Character $character, ?InventorySet $inventorySet = null) {
        if ($this->autoDisenchanted($item, $character)) {
            return;
        }

        if ($this->handleOverFlow($item, $character, $inventorySet)) {
            return;
        }

        if ($this->giveItemToPlayer($item, $character)) {
            return;
        }

        event(new UpdateTopBarEvent($character));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));
    }

    public function autoDisenchanted(Item $item, Character $character): bool {
        if ($item->type !== 'quest' && !is_null($character->user->auto_disenchant_amount)) {
            if ($this->autoDisenchant($character, $item)) {
                return true;
            }
        }

        return false;
    }

    public function handleOverFlow(Item $item, Character $character, ?InventorySet $inventorySet = null): bool {
        $user = $character->user;

        if ($character->isInventoryFull() && !is_null($inventorySet) && $item->type !== 'quest') {
            $this->inventorySetService->putItemIntoSet($inventorySet, $item);

            if (!is_null($characterSet->name)) {
                $message = 'Item: '.$item->affix_name.' has been stored in Set: '.$inventorySet->name.' as your inventory is full';

                event(new ServerMessageEvent($user, $message));

                return true;
            } else {
                $index     = $character->inventorySets->search(function($set) use ($inventorySet) {
                    return $set->id === $inventorySet->id;
                });

                $message = 'Item: '.$item->affix_name.' has been stored in Set: '.($index + 1).' as your inventory is full';

                event(new ServerMessageEvent($user, $message));

                return true;
            }

        } else if ($item->type !== 'quest' && $character->isInventoryFull()) {
            $message = 'You failed to get the item: '.$item->affix_name.' as your inventory is full and you have no empty set.';

            event(new ServerMessageEvent($user, $message));

            return true;
        }

        return false;
    }

    public function giveItemToPlayer(Item $item, Character $character): bool {
        $user = $character->user;

        if ($item->type === 'quest') {
            if ($this->canHaveItem($character, $item)) {
                $character->inventory->slots()->create([
                    'inventory_id' => $character->inventory->id,
                    'item_id'      => $item->id,
                ]);

                $message = $character->name . ' has found: ' . $item->affix_name;

                broadcast(new GlobalMessageEvent($message));

                event(new ServerMessageEvent($user, 'You gained the item: ' . $item->affix_name));

                return true;
            }
        } else  {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            event(new ServerMessageEvent($user, 'You gained the item: ' . $item->affix_name));

            return true;
        }

        return false;
    }

    protected function autoDisenchant(Character $character, Item $item) {
        $user = $character->user;

        if ($user->auto_disenchant_amount === 'all') {
            AdventureItemDisenchantJob::dispatch($character, $item)->delay(now()->addSeconds(30));

            $this->messages[] = 'Item: '.$item->affix_name.' has been set to be disenchanted. (Item may have already been disenchanted if you see no message in chat)';

            return true;
        }

        if ($user->auto_disenchant_amount === '1-billion') {
            $cost = SellItemCalculator::fetchSalePriceWithAffixes($this->item);

            if ($cost < 1000000000) {
                AdventureItemDisenchantJob::dispatch($character, $item);

                $this->messages[] = 'Item: '.$item->affix_name.' has been set to be disenchanted. (Item may have already been disenchanted if you see no message in chat)';

                return true;
            }
        }

        return false;
    }
}
