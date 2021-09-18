<?php

namespace App\Game\Skills\Jobs;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\AlchemyService;
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

class ProcessAlchemy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $itemToCraft;

    /**
     * @param Character $character
     * @param int $itemToCraft
     */
    public function __construct(Character $character, int $itemToCraft)
    {
        $this->character   = $character;
        $this->itemToCraft = $itemToCraft;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AlchemyService $alchemyService) {
        $alchemyService->transmute($this->character, $this->itemToCraft);
    }
}
