<?php

namespace App\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use Illuminate\Console\Command;

class FixEventTypesOnEvents extends Command
{

    const MONTHLY_PVP = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:event-types-on-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix event type on events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventTypes = [
            EventType::WEEKLY_CELESTIALS => 0,
            EventType::WEEKLY_CURRENCY_DROPS => 2,
            EventType::RAID_EVENT => 3,
            EventType::WINTER_EVENT => 4,
            EventType::PURGATORY_SMITH_HOUSE => 5,
            EventType::GOLD_MINES => 6,
            EventType::THE_OLD_CHURCH => 7,
            EventType::DELUSIONAL_MEMORIES_EVENT => 8,
            EventType::WEEKLY_FACTION_LOYALTY_EVENT => 9,
            EventType::FEEDBACK_EVENT => 10,
        ];

        foreach ($eventTypes as $eventType => $originalValue) {
            if ($eventType === self::MONTHLY_PVP) {
                ScheduledEvent::where('event_type', self::MONTHLY_PVP)->delete();
                Event::where('type', self::MONTHLY_PVP)->delete();
            }

            if ($eventType === EventType::WEEKLY_CURRENCY_DROPS) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::RAID_EVENT) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', EventType::RAID_EVENT)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::WINTER_EVENT) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => EventType::WINTER_EVENT - 1
                ]);
                Event::where('type', EventType::WINTER_EVENT)->update([
                    'type' => EventType::WINTER_EVENT - 1
                ]);
            }

            if ($eventType === EventType::PURGATORY_SMITH_HOUSE) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::GOLD_MINES) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::THE_OLD_CHURCH) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::DELUSIONAL_MEMORIES_EVENT) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::WEEKLY_FACTION_LOYALTY_EVENT) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }

            if ($eventType === EventType::FEEDBACK_EVENT) {
                ScheduledEvent::where('event_type', $originalValue)->update([
                    'event_type' => $originalValue - 1
                ]);
                Event::where('type', $originalValue)->update([
                    'type' => $originalValue - 1
                ]);
            }
        }
    }
}
