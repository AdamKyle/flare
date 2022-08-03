<?php

namespace App\Game\Core\Jobs;

use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Game\Core\Services\AdventureItemRewardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class HandleAdventureRewardItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $character;

    protected $item;

    protected $inventorySet;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character, Item $item, ?InventorySet $set = null) {
        $this->character    = $character;
        $this->item         = $item;
        $this->inventorySet = $set;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AdventureItemRewardService $adventureItemRewardService) {
        $adventureItemRewardService->handleItem($this->item, $this->character, $this->inventorySet);
    }
}
