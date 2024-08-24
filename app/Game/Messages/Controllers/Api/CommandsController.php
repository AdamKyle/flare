<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Messages\Request\PublicEntityRequest;
use App\Game\Messages\Services\PublicEntityCommand;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CommandsController extends Controller
{
    private PublicEntityCommand $publicEntityCommand;

    public function __construct(PublicEntityCommand $publicEntityCommand)
    {
        $this->publicEntityCommand = $publicEntityCommand;
    }

    public function publicEntity(PublicEntityRequest $request): JsonResponse
    {

        $user = auth()->user();

        $command = $this->publicEntityCommand->setCharacter($user);

        if ($request->attempt_to_teleport) {

            if ($user->character->is_dead) {
                ServerMessageHandler::sendBasicMessage($user, 'You are dead. How are you suppose to teleport? Resurrect child!');

                return response()->json();
            }

            $command->usePCTCommand();

            return response()->json();
        }

        $command->usPCCommand();

        return response()->json();
    }
}
