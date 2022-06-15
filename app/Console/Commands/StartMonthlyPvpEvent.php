<?php

namespace App\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Values\EventType;
use App\Game\Battle\Services\MonthlyPvpService;
use Illuminate\Console\Command;

class StartMonthlyPvpEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:pvp-monthly-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the pvp event';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MonthlyPvpService $monthlyPvpService) {
        Event::where('type', EventType::MONTHLY_PVP)->delete();

        $monthlyPvpService->moveParticipatingPlayers();
    }
}
