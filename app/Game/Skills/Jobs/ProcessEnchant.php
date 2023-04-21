<?php

namespace App\Game\Skills\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Character;
use App\Game\Skills\Services\EnchantingService;

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
