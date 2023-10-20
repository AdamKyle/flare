<?php

namespace App\Console\Commands;

use App\Flare\Models\ScheduledEventConfiguration;
use App\Flare\Services\EventSchedulerService;
use Illuminate\Console\Command;

class GenerateScheduledEvents extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:scheduled-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'based on ScheduledEventConfiguration, this will generate a set of events.';

    /**
     * Execute the console command.
     */
    public function handle(EventSchedulerService $eventSchedulerService) {

        // Bail if we have none.
        if (ScheduledEventConfiguration::count() === 0) {
            return;
        }

        $scheduledEventConfigurations = ScheduledEventConfiguration::all();

        foreach ($scheduledEventConfigurations as $scheduledEventConfiguration) {

            if ($scheduledEventConfiguration->generate_every === 'weekly') {
                $this->handleWeeklyEvents($scheduledEventConfiguration, $eventSchedulerService);
            }

            if ($scheduledEventConfiguration->generate_every === 'monthly') {
                $this->handleMonthlyEvents($scheduledEventConfiguration, $eventSchedulerService);
            }
        }
    }

    protected function handleWeeklyEvents(ScheduledEventConfiguration $scheduledEventConfiguration, EventSchedulerService $eventSchedulerService): void {
        $futureLastGenerated = $scheduledEventConfiguration->last_time_generated->copy()->addWeeks(2);

        if (now()->gte($futureLastGenerated)) {
            $eventSchedulerService->generateFutureEvents($scheduledEventConfiguration);
        }
    }

    protected function handleMonthlyEvents(ScheduledEventConfiguration $scheduledEventConfiguration, EventSchedulerService $eventSchedulerService): void {
        $futureLastGenerated = $scheduledEventConfiguration->last_time_generated->copy()->addMonths(2);

        if (now()->gte($futureLastGenerated)) {
            $eventSchedulerService->generateFutureEvents($scheduledEventConfiguration);
        }
    }
}
