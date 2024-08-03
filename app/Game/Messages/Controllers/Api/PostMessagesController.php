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
    private PublicMessage $publicMessage;

    private PrivateMessage $privateMessage;

    public function __construct(PublicMessage $publicMessage, PrivateMessage $privateMessage)
    {
        $this->publicMessage = $publicMessage;
        $this->privateMessage = $privateMessage;
    }

    public function postPublicMessage(PublicMessageRequest $request): JsonResponse
    {

        $this->publicMessage->postPublicMessage($request->message);

        return response()->json();
    }

    public function sendPrivateMessage(PrivateMessageRequest $request): JsonResponse
    {

        $this->privateMessage->sendPrivateMessage($request->user_name, $request->message);

        return response()->json();
    }
}
