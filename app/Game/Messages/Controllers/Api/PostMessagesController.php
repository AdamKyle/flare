<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Request\PrivateMessageRequest;
use App\Game\Messages\Services\PrivateMessage;
use Illuminate\Http\JsonResponse;
use App\Game\Messages\Request\PublicMessageRequest;
use App\Game\Messages\Services\PublicMessage;
use App\Http\Controllers\Controller;


class PostMessagesController extends Controller {

    /**
     * @var PublicMessage $publicMessage
     */
    private PublicMessage $publicMessage;

    /**
     * @var PrivateMessage $privateMessage
     */
    private PrivateMessage $privateMessage;

    /**
     * @param PublicMessage $publicMessage
     * @param PrivateMessage $privateMessage
     */
    public function __construct(PublicMessage $publicMessage, PrivateMessage $privateMessage) {
        $this->publicMessage  = $publicMessage;
        $this->privateMessage = $privateMessage;
    }

    /**
     * @param PublicMessageRequest $request
     * @return JsonResponse
     */
    public function postPublicMessage(PublicMessageRequest $request): JsonResponse {

        $this->publicMessage->postPublicMessage($request->message);

        return response()->json();
    }

    /**
     * @param PrivateMessageRequest $request
     * @return JsonResponse
     */
    public function sendPrivateMessage(PrivateMessageRequest $request): JsonResponse {

        $this->privateMessage->sendPrivateMessage($request->user_name, $request->message);

        return response()->json();
    }
}
