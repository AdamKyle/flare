<?php

namespace App\Game\Messages\Controllers\Api;

use App\Flare\Models\Announcement;
use App\Game\Messages\Services\FetchMessages;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class FetchMessagesController extends Controller
{
    public function __construct(private readonly FetchMessages $fetchMessages)
    {
    }

    public function fetchChatMessages(): JsonResponse
    {
        return response()->json([
            'chat_messages' => $this->fetchMessages->fetchMessages(),
        ]);
    }
}
