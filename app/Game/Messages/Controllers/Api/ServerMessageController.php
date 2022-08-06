<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Services\ServerMessage;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Game\Messages\Request\ServerMessageRequest;


class ServerMessageController extends Controller {

    /**
     * @var ServerMessage $serverMessage
     */
    private ServerMessage $serverMessage;

    /**
     * @param ServerMessage $serverMessage
     */
    public function __construct(ServerMessage $serverMessage) {
        $this->serverMessage = $serverMessage;
    }

    /**
     * @param ServerMessageRequest $request
     * @return JsonResponse
     */
    public function generateServerMessage(ServerMessageRequest $request): JsonResponse {

        if ($request->has('custom_message')) {
            $this->serverMessage->generateServerMessageForCustomMessage($request->custom_message);

            return response()->json();
        }

        if ($request->has('type')) {
            $this->serverMessage->generateServerMessage($request->type);

            return response()->json();
        }

        return response()->json([
            'message' => 'Cannot generate server message for either type or custom message.',
        ], 422);
    }
}
