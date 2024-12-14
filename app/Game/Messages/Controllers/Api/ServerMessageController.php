<?php

namespace App\Game\Messages\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Game\Messages\Factories\AssignMessageType;
use App\Game\Messages\Request\ServerMessageRequest;
use App\Game\Messages\Services\ServerMessage;
use App\Http\Controllers\Controller;

class ServerMessageController extends Controller
{

    /**
     * @param ServerMessage $serverMessage
     * @param AssignMessageType $assignMessageType
     */
    public function __construct(private ServerMessage $serverMessage, private AssignMessageType $assignMessageType) {}

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
            try {
                $type = $this->assignMessageType->assignType($request->type);

                $this->serverMessage->generateServerMessage($type);

                return response()->json();
            } catch (Exception $e) {
                Log::error('[ServerMessageController@generateServerMessage] error: ' . $e->getmessage());

                return response()->json([
                    'message' => 'Invalid message type was passed when trying to generate server message'
                ], 422);
            }
        }

        return response()->json([
            'message' => 'Cannot generate server message for either type or custom message.',
        ], 422);
    }
}
