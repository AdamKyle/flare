<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Request\ServerMessageRequest;
use App\Game\Messages\Services\ServerMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ServerMessageController extends Controller
{

    public function __construct(private ServerMessage $serverMessage)
    {
        $this->serverMessage = $serverMessage;
    }

    /**
     * @param ServerMessageRequest $request
     * @return JsonResponse
     */
    public function generateServerMessage(ServerMessageRequest $request): JsonResponse
    {

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
