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
        $options = EventType::getOptionsForSelect();
        $options['all'] = 'All Events';

        $selectedEvent = $this->choice(
            'Which event do you want to create?',
            array_values($options)
        );

        if ($selectedEvent === 'All Events') {
            $this->createAllEvents($options);
        } else {
            $eventType = array_search($selectedEvent, $options);

            if ($eventType !== false) {
                $this->createEvent($eventType, $selectedEvent);
            } else {
                $this->error('Invalid event type selected.');
            }
        }
    }

    /**
     * Create a specific event.
     */
    private function createEvent(int $eventType, string $eventName): void
    {

        if ($eventType === EventType::RAID_EVENT) {

            $selectedRaid = $this->choice(
                'Which raid do you want to create?',
                Raid::pluck('name')->toArray()
            );

            $raid = Raid::where('name', $selectedRaid)->first();

            ScheduledEvent::create([
                'event_type' => $eventType,
                'raid_id' => $raid->id,
                'start_date' => now()->addMinutes(5),
                'end_date' => now()->addMinutes(10),
                'description' => $eventName,
                'currently_running' => false,
            ]);

            $this->info("Created RAID event: {$eventName}");
        } else {
            ScheduledEvent::create([
                'event_type' => $eventType,
                'raid_id' => null,
                'start_date' => now()->addMinutes(5),
                'end_date' => now()->addMinutes(10),
                'description' => $eventName,
                'currently_running' => false,
            ]);

            $this->info("Created event: {$eventName}");
        }
    }

    /**
     * Create all available events.
     */
    private function createAllEvents(array $options): void
    {
        foreach ($options as $eventType => $eventName) {
            if ($eventName === 'All Events') {
                continue;
            }

            $this->createEvent($eventType, $eventName);
        }

        $this->info('All events have been created successfully.');
    }
}
