<?php

namespace App\Game\Skills\Jobs;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ShowTimeOutEvent;
use Illuminate\Support\Facades\Cache;

class ProcessEnchant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $params;

    private $slot;

    /**
     * @param Character $character
     * @param InventorySlot $slot
     * @param array $params
     */
    public function __construct(Character $character, InventorySlot $slot, array $params)
    {
        $this->character = $character;
        $this->params    = $params;
        $this->slot      = $slot;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EnchantingService $enchantingService) {
        $enchantingService->enchant($this->character, $this->params, $this->slot);
    }
}
