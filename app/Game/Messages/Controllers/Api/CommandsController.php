<?php

namespace App\Game\Messages\Controllers\Api;

use App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Game\Messages\Request\PublicEntityRequest;
use App\Game\Messages\Services\PublicEntityCommand;

class CommandsController extends Controller {

    /**
     * @var PublicEntityCommand $publicEntityCommand
     */
    private PublicEntityCommand $publicEntityCommand;

    /**
     * @param PublicEntityCommand $publicEntityCommand
     */
    public function __construct(PublicEntityCommand $publicEntityCommand) {
        $this->publicEntityCommand = $publicEntityCommand;
    }


    /**
     * @param PublicEntityRequest $request
     * @return JsonResponse
     */
    public function publicEntity(PublicEntityRequest $request): JsonResponse {

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
