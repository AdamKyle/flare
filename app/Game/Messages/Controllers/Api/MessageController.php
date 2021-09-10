<?php

namespace App\Game\Messages\Controllers\Api;


use App\Flare\Models\CelestialFight;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Request\PublicEntityRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Events\PrivateMessageEvent;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Flare\Models\Character;
use App\Game\Messages\Models\Message;
use App\Flare\Models\User;
use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Models\Npc;
use App\Game\Messages\Values\MapChatColor;
use App\Game\Messages\Handlers\NpcCommandHandler;

class MessageController extends Controller {

    private $serverMessage;

    private $npcCommandHandler;

    public function __construct(ServerMessageBuilder $serverMessage, NpcCommandHandler $npcCommandHandler) {
        $this->serverMessage     = $serverMessage;
        $this->npcCommandHandler = $npcCommandHandler;
    }

    public function fetchUserInfo(Request $request, User $user) {
        return response()->json([
            'user' => [
                'is_silenced'       => $user->is_silenced,
                'can_talk_again_at' => $user->can_speak_again_at,
            ]
        ], 200);
    }

    public function fetchMessages() {
        $messages = Message::with(['user', 'user.roles', 'user.character'])
                            ->where('from_user', null)
                            ->where('to_user', null)
                            ->where('created_at', '>=', now()->subHour())
                            ->orderBy('created_at', 'desc')
                            ->take(15)
                            ->get()
                            ->transform(function($message) {
                                $message->x    = $message->x_position;
                                $message->y    = $message->y_position;
                                $message->name = $message->user->hasRole('Admin') ? 'Admin' : $message->user->character->name;

                                $mapName = '';

                                switch ($message->color) {
                                    case '#ffffff':
                                        $mapName = 'SUR';
                                        break;
                                    case '#ffad47':
                                        $mapName = 'LABY';
                                        break;
                                    case '#755c59':
                                        $mapName = 'DUN';
                                        break;
                                    default:
                                        $mapName = 'SUR';
                                }

                                $message->map = $mapName;

                                return $message;
                            })
                            ->all();

        return response()->json(
            $messages,
            200
        );
    }

    public function postPublicMessage(Request $request) {
        $x         = 0;
        $y         = 0;
        $color     = null;
        $mapName   = null;

        $message = auth()->user()->messages()->create([
            'message'    => $request->message,
            'x_position' => $x,
            'y_position' => $y,
        ]);

        if (!auth()->user()->hasRole('Admin')) {
            $character = auth()->user()->character;

            $x     = $character->map->character_position_x;
            $y     = $character->map->character_position_y;

            switch ($character->map->gameMap->name) {
                case 'Surface':
                    $mapName = 'SUR';
                    break;
                case 'Labyrinth':
                    $mapName = 'LABY';
                    break;
                case 'Dungeons':
                    $mapName = 'DUN';
                    break;
                default:
                    $mapName = 'SUR';
            }

            $color = (new MapChatColor($character->map->gameMap->name))->getColor();

            $message->color      = $color;
            $message->x_position = $x;
            $message->y_position = $y;

            $message->save();
        }

        $message = $message->refresh();

        if (!is_null($mapName)) {
            $message->map_name = $mapName;
        }

        broadcast(new MessageSentEvent(auth()->user(), $message))->toOthers();

        $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

        broadcast(new UpdateAdminChatEvent($adminUser));

        return response()->json([], 200);
    }

    public function generateServerMessage(Request $request) {
        if ($request->type === 'chatting_to_much') {
            $handler = resolve(MessageThrottledHandler::class);

            $handler->forUser(auth()->user())->increaseThrottleCount()->silence();
        }

        broadcast(new ServerMessageEvent(auth()->user(), $this->serverMessage->build($request->type)));

        return response()->json([], 200);
    }

    public function sendPrivateMessage(Request $request) {
        $character = Character::where('name', '=', $request->user_name)->first();
        $user      = auth()->user();

        if (!is_null($character)) {
            $user->messages()->create([
                'from_user' => $user->id,
                'to_user'   => $character->user->id,
                'message'   => $request->message,
            ]);

            broadcast(new PrivateMessageEvent($user, $character->user, $request->message));

            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

            broadcast(new UpdateAdminChatEvent($adminUser));

            return response()->json([], 200);
        }

        $npc = Npc::where('name', $request->user_name)->first();

        if (!is_null($npc)) {
            $command = $npc->commands->where('command', $request->message)->first();

            if (!is_null($command)) {
                $this->npcCommandHandler->handleForType($command->command_type, $npc, auth()->user());

                return response()->json([], 200);
            }

            broadcast(new ServerMessageEvent($user, $this->serverMessage->build('no_matching_command')));

            return response()->json([], 200);
        }

        broadcast(new ServerMessageEvent($user, $this->serverMessage->build('no_matching_user')));


        return response()->json([], 200);
    }

    public function publicEntity(PublicEntityRequest $request, MovementService $movementService) {
        $user = auth()->user();

        $celestial = CelestialFight::where('type', CelestialConjureType::PUBLIC)->first();

        $map = $celestial->monster->gameMap;
        $x   = $celestial->x_position;
        $y   = $celestial->y_position;

        if (is_null($celestial)) {
            broadcast(new ServerMessageEvent($user, 'There are no celestials in the world right now child!'));

            return response()->json([], 200);
        }

        if ($request->attempt_to_teleport) {
            $hasItem = $user->character->inventory->slots->filter(function($slot) {
                if ($slot->item->type === 'quest' && !is_null($slot->item->effect)) {
                    return (new ItemEffectsValue($slot->item->effect))->teleportToCelestial();
                }
            })->isNotEmpty();

            if ($hasItem) {
                if (!$map->default) {
                    $traverse = $movementService->updateCharacterPlane($celestial->monster->gameMap->id, $user->character);
                }

                if ($traverse['status'] === 422) {
                    broadcast(new ServerMessageEvent($user, $traverse['message']));
                    return response()->json([], 200);
                }

                $movement = $movementService->teleport($user->character, $celestial->x_position, $celestial->y_position, 0 , 0);

                if ($movement['status'] === 422) {
                    broadcast(new ServerMessageEvent($user, $movement['message']));
                    return response()->json([], 200);
                }

                $message = 'Child! ' . $celestial->monster->name  .' is at (X/Y): '. $x .'/'. $y. ' on the: '. $map->name .'Plane. I have teleported you there free of charge!';
            } else {
                broadcast(new ServerMessageEvent($user, 'You are missing a quest item to do that child.'));

                return response()->json([], 200);
            }
        } else {
            $message = 'Child! ' . $celestial->monster->name  .' is at (X/Y): '. $x .'/'. $y. ' on the: '. $map->name .'Plane.';
        }

        broadcast(new ServerMessageEvent($user, $message));

        return response()->json([], 200);
    }
}
