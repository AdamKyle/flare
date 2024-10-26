<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use Illuminate\Console\Command;

class CreateEventsForDevelopment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:events-for-development';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates events for development purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        foreach (EventType::getOptionsForSelect() as $eventType => $eventName) {

            if ($eventType === EventType::RAID_EVENT) {
                $raid = Raid::first();

                ScheduledEvent::create([
                    'event_type' => $eventType,
                    'raid_id' => $raid->id,
                    'start_date' => now()->addMinutes(5),
                    'end_date' => now()->addMinutes(10),
                    'description' => $eventName,
                    'currently_running' => false,
                ]);

                continue;
            }

            ScheduledEvent::create([
                'event_type' => $eventType,
                'raid_id' => null,
                'start_date' => now()->addMinutes(5),
                'end_date' => now()->addMinutes(10),
                'description' => $eventName,
                'currently_running' => false,
            ]);
        }
    }
}
