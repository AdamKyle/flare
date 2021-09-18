<?php

namespace App\Game\Skills\Jobs;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ShowTimeOutEvent;
use Illuminate\Support\Facades\Cache;

class ProcessCraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $params;

    /**
     * @param Character $character
     * @param array $params
     */
    public function __construct(Character $character, array $params)
    {
        $this->character = $character;
        $this->params    = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CraftingService $craftingService) {
        $craftingService->craft($this->character, $this->params);
    }
}
