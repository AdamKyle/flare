<?php

namespace App\Game\Messages\Controllers\Api;

use App\Flare\Models\Announcement;
use App\Game\Messages\Services\AnnouncementPresenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AnnouncementsController extends Controller
{
    /**
     * @param AnnouncementPresenter $announcementPresenter
     */
    public function __construct(
        private readonly AnnouncementPresenter $announcementPresenter,
    ) {}

    /**
     * Return every Announcement, decorated for player-facing display.
     *
     * @return JsonResponse
     */
    public function fetchAnnouncements(): JsonResponse
    {
        $announcements = Announcement::orderByDesc('id')->get()->transform(
            fn (Announcement $announcement) => $this->announcementPresenter->present($announcement)
        );

        return response()->json($announcements);
    }
}
