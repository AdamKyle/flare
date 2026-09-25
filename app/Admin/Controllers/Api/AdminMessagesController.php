<?php

namespace App\Admin\Controllers\Api;

use App\Game\Messages\Services\FetchMessages;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminMessagesController extends Controller
{
    /**
     * @param FetchMessages $fetchMessages
     */
    public function __construct(private readonly FetchMessages $fetchMessages) {}

    /**
     * Return the public chat messages from the previous 30 days for the Admin chat.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'chat_messages' => $this->fetchMessages->fetchAdminMessages(),
        ]);
    }
}
