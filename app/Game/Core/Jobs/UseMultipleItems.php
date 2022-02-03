<?php

namespace App\Game\Core\Jobs;

use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Services\UseItemService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ShowTimeOutEvent;
use Illuminate\Support\Facades\Cache;

class UseMultipleItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var int $slotId
     */
    private $slotId;

    /**
     * @param Character $character
     * @param InventorySlot $slot
     */
    public function __construct(Character $character, int $slotId)
    {
        $this->character = $character;
        $this->slotId    = $slotId;
    }

    /**
     * Execute the job.
     *
     * @param UseItemService $useItemService
     */
    public function handle(UseItemService $useItemService) {
        $inventorySlot = InventorySlot::where('inventory_id', $this->character->inventory->id)
                                      ->where('id', $this->slotId)
                                      ->first();

        $character = $this->character->refresh();

        // If less than 11 it will only apply up to a total of ten boons.
        if ($character->boons->count() < 11) {
            $useItemService->useItem($inventorySlot, $character, $inventorySlot->item);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new UpdateTopBarEvent($character));

            event(new UpdateCharacterAttackEvent($character));
        }
    }
}
