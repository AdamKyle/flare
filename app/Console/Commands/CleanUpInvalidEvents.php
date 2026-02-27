<?php

namespace App\Console\Commands;

use App\Flare\Models\Announcement;
use App\Flare\Models\ScheduledEvent;
use Illuminate\Console\Command;

class CleanUpInvalidEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean-up:invalid-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up invalid events and announcements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $announcements = Announcement::all();

        foreach ($announcements as $announcement) {
            if (is_null($announcement->event)) {
                $announcement->delete();

                continue;
            }

            $event = $announcement->event;

            $scheduledEvent = ScheduledEvent::where('event_type', $event->type)->orderBy('start_date', 'desc')->first();

            if (is_null($scheduledEvent)) {
                $event->delete();
                $announcement->delete();

                continue;
            }

            if ($event->ends_at->lte(now()) && $scheduledEvent->currently_running) {
                $scheduledEvent->update([
                    'currently_running' => false,
                ]);

                $event->delete();
                $announcement->delete();
            }
        }
    }
}
