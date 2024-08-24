<?php

namespace App\Game\Battle\Jobs;

use App\Game\Battle\Services\MonthlyPvpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonthlyPvpAutomation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(MonthlyPvpService $monthlyPvpService)
    {
        $monthlyPvpService->moveParticipatingPlayers();
    }
}
