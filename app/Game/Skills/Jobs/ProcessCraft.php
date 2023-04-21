<?php

namespace App\Game\Skills\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Game\Skills\Services\CraftingService;
use App\Flare\Models\Character;

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
