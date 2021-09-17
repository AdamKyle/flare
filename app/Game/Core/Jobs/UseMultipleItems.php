<?php

namespace App\Game\Core\Jobs;

use App\Flare\Models\InventorySlot;
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
     * @var InventorySlot $slot
     */
    private $slot;

    /**
     * @param Character $character
     * @param InventorySlot $slot
     */
    public function __construct(Character $character, InventorySlot $slot)
    {
        $this->character = $character;
        $this->slot      = $slot;
    }

    /**
     * Execute the job.
     *
     * @param UseItemService $useItemService
     */
    public function handle(UseItemService $useItemService) {
        // If less then 11 it will only apply up to a total of ten boons.
        if ($this->character->refresh()->boons->count() < 11) {
            $useItemService->useItem($this->slot, $this->character, $this->slot->item);

            event(new CharacterInventoryUpdateBroadCastEvent($this->character->user));
        }
    }
}
