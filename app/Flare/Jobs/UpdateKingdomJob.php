<?php

namespace App\Flare\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomUpdateService;

class UpdateKingdomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Kingdom $user
     */
    public $kingdom;

    /**
     * Create a new job instance.
     *
     * @param Kingdom $kingdom
     */
    public function __construct(Kingdom $kingdom) {
        $this->kingdom = $kingdom;
    }

    public function handle(KingdomUpdateService $kingdomUpdateService) {
        $kingdomUpdateService->setKingdom($this->kingdom)->updateKingdom();
    }
}
