<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Request\PrivateMessageRequest;
use App\Game\Messages\Request\PublicMessageRequest;
use App\Game\Messages\Services\PrivateMessage;
use App\Game\Messages\Services\PublicMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PostMessagesController extends Controller
{
    /**
     * @param PublicMessage $publicMessage
     * @param PrivateMessage $privateMessage
     */
    public function __construct(
        private readonly PublicMessage $publicMessage,
        private readonly PrivateMessage $privateMessage,
    ) {}

    /**
     * Post a public chat message for the authenticated user.
     *
     * @param PublicMessageRequest $request
     * @return JsonResponse
     */
    public function postPublicMessage(PublicMessageRequest $request): JsonResponse
    {
        $this->publicMessage->postPublicMessage($request->message);

        return response()->json();
    }

    /**
     * Send a private message and report whether it was delivered.
     *
     * @param PrivateMessageRequest $request
     * @return JsonResponse
     */
    public function sendPrivateMessage(PrivateMessageRequest $request): JsonResponse
    {
        $delivered = $this->privateMessage->sendPrivateMessage($request->user_name, $request->message);

        return response()->json([
            'delivered' => $delivered,
        ]);
    }
}
