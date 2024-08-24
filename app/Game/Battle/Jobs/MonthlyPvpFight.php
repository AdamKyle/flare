<?php

namespace App\Game\Battle\Jobs;

use App\Game\Battle\Services\MonthlyPvpFightService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonthlyPvpFight implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $participants;

    public function __construct(Collection $participants)
    {
        $this->participants = $participants;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function handle(monthlyPvpFightService $monthlyPvpFightService)
    {
        $monthlyPvpFightService->setRegisteredParticipants($this->participants)->startPvp();
    }
}
