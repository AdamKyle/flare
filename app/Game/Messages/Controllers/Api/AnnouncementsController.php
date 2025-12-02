<?php

namespace App\Game\Messages\Controllers\Api;

use App\Flare\Models\Announcement;
use App\Game\Events\Values\EventType;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AnnouncementsController extends Controller
{
    public function fetchAnnouncements(): JsonResponse
    {
        return response()->json(Announcement::orderByDesc('id')->get()->transform(function ($announcement) {
            $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');
            $announcement->event_name = (new EventType($announcement->event->type))->getNameForEvent();

            return $announcement;
        }));
    }
}
