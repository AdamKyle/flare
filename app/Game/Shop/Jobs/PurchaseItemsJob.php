<?php

namespace App\Game\Shop\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use App\Flare\Models\Character;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class PurchaseItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $items;

    /**
     * EndGlobalTimeOut constructor.
     *
     * @param Character $character
     * @param Collection $items
     */
    public function __construct(Character $character, Collection $items) {
        $this->character = $character;
        $this->items     = $items;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->items as $item) {
            $character = $this->character->refresh();

            if ($character->isInventoryFull()) {
                event(new ServerMessageEvent($character->user, 'Inventory is full, item not bought. Please make room.'));

                return;
            }

            if ($item->cost > $character->gold) {
                event(new ServerMessageEvent($character->user, 'You do not have enough gold to buy: ' . $item->name . '. Anything before this item in the list was purchased.'));

                return;
            }

            event(new BuyItemEvent($item, $character));

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new UpdateTopBarEvent($character));

            event(new ServerMessageEvent($character->user, 'Purchased: ' . $item->name));
        }

        event(new ServerMessageEvent($character->user, 'Purchased all selected items from the shop!'));
    }
}
