<?php

namespace App\Admin\Import\Events\Sheets;

use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EventsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $event = array_combine($rows[0]->toArray(), $row->toArray());

                $raid = Raid::where('name', $event['raid_id'])->first();

                if (!is_null($raid)) {
                    $event['raid_id'] = $raid->id;

                    $foundEvent = ScheduledEvent::where('start_date', $event['start_date'])->where('end_date', $event['end_date'])->where('raid_id', $raid->id)->first();

                    $this->handleEvent($event, $foundEvent);

                    continue;
                }

                $this->handleEvent($event);
            }
        }
    }

    /**
     * Handle updateing or creating data.
     *
     * @param array $eventData
     * @param ScheduledEvent|null $scheduledEvent
     * @return void
     */
    protected function handleEvent(array $eventData, ?ScheduledEvent $scheduledEvent = null): void {
        if (!is_null($scheduledEvent)) {

            $scheduledEvent->update($eventData);
        } else {
            
            ScheduledEvent::create($eventData);
        }
    }
}
