<?php

namespace App\Game\Messages\Controllers\Api;

use App\Flare\Models\Announcement;
use App\Game\Events\Values\EventType;
use App\Game\Raids\Values\RaidType;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;

class AnnouncementsController extends Controller
{
    /**
     * @throws Exception
     */
    public function fetchAnnouncements(): JsonResponse
    {
        return response()->json(Announcement::orderByDesc('id')->get()->transform(function ($announcement) {
            $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');

            $eventType = new EventType($announcement->event->type);
            $eventName = $eventType->getNameForEvent();

            if ($eventType->isRaidEvent()) {
                $eventName = new RaidType($announcement->event->raid->raid_type)->getNameForRaid();
            }

            $announcement->event_name = $eventName;

            return $announcement;
        }));
    }
}
