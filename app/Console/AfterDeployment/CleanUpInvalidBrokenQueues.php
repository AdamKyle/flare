<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\CharacterAutomation;
use App\Game\Kingdoms\Service\CapitalCityQueueCleanupService;
use App\Flare\Values\AutomationType;
use Illuminate\Console\Command;

class CleanUpInvalidBrokenQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:invalid-broken-queues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up invalid broken queues';

    /**
     * Execute the console command.
     */
    public function handle(CapitalCityQueueCleanupService $capitalCityQueueCleanupService): void
    {
        $capitalCityQueueCleanupService->clean();

        $startedAtCutoff = now()->subHours(8);
        $completedAtCutoff = now()->subMinutes(10);

        CharacterAutomation::query()
            ->where(function ($query) {
                $query->where('type', AutomationType::DELVE)
                    ->orWhere('type', AutomationType::EXPLORING);
            })
            ->where(function ($query) use ($startedAtCutoff, $completedAtCutoff) {
                $query->where('started_at', '<=', $startedAtCutoff)
                    ->orWhere('completed_at', '<=', $completedAtCutoff);
            })
            ->delete();
    }
}
