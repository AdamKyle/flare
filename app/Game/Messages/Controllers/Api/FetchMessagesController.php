<?php

namespace App\Game\Messages\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Flare\Models\Announcement;
use App\Game\Messages\Services\FetchMessages;
use App\Http\Controllers\Controller;

class FetchMessagesController extends Controller {

    /**
     * @var FetchMessages $fetchMessages
     */
    private FetchMessages $fetchMessages;

    /**
     * @param FetchMessages $fetchMessages
     */
    public function __construct(FetchMessages $fetchMessages) {
        $this->fetchMessages = $fetchMessages;
    }

    /**
     * @return JsonResponse
     */
    public function fetchChatMessages(): JsonResponse {
        return response()->json([
            'chat_messages' => $this->fetchMessages->fetchMessages(),
            'announcements' => Announcement::orderByDesc('id')->get()->transform(function($announcement) {
                $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');

                return $announcement;
            }),
        ]);
    }
}
