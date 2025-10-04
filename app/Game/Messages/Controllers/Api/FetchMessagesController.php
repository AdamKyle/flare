<?php

namespace App\Game\Messages\Controllers\Api;

use App\Flare\Models\Announcement;
use App\Game\Messages\Services\FetchMessages;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class FetchMessagesController extends Controller
{
    public function __construct(private FetchMessages $fetchMessages)
    {
        $this->fetchMessages = $fetchMessages;
    }

    public function fetchChatMessages(): JsonResponse
    {
        return response()->json([
            'chat_messages' => $this->fetchMessages->fetchMessages(),
            'announcements' => Announcement::orderByDesc('id')->get()->transform(function ($announcement) {
                $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');

                return $announcement;
            }),
        ]);
    }
}
