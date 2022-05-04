<?php

namespace App\Game\Messages\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Models\User;
use App\Game\Messages\Request\PublicEntityRequest;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Services\NpcCommandService;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Events\PrivateMessageEvent;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Models\Message;
use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Models\Npc;
use App\Game\Messages\Values\MapChatColor;

class MessageController extends Controller {

    private $serverMessage;

    private $npcCommandService;

    public function __construct(
        ServerMessageBuilder $serverMessage,
        NpcCommandService $npcCommandService)
    {
        $this->serverMessage     = $serverMessage;
        $this->npcCommandService = $npcCommandService;
    }

    public function fetchUserInfo(User $user) {
        return response()->json([
            'user' => [
                'is_silenced'       => $user->is_silenced,
                'can_talk_again_at' => $user->can_speak_again_at,
            ]
        ], 200);
    }

    public function fetchMessages(User $user) {
        $messages = Message::with(['user', 'user.roles', 'user.character'])
                            ->where('from_user', null)
                            ->where('to_user', null)
                            ->where('created_at', '>=', now()->subDay())
                            ->orderBy('created_at', 'desc')
                            ->take(1000)
                            ->get()
                            ->transform(function($message) {
                                $message->x    = $message->x_position;
                                $message->y    = $message->y_position;
                                $message->name = $message->user->hasRole('Admin') ? 'Admin' : $message->user->character->name;

                                switch ($message->color) {
                                    case '#ffffff':
                                        $mapName = 'SUR';
                                        break;
                                    case '#ffad47':
                                        $mapName = 'LABY';
                                        break;
                                    case '#ccb9a5':
                                        $mapName = 'DUN';
                                        break;
                                    case '#ababab':
                                        $mapName = 'SHP';
                                        break;
                                    case '#639cff':
                                        $mapName = 'PURG';
                                        break;
                                    default:
                                        $mapName = 'SUR';
                                }

                                $message->map = $mapName;

                                return $message;
                            })
                            ->all();

        return response()->json(
            array_merge($messages, [
                'is_silenced'       => $user->is_silenced,
                'can_talk_again_at' => $user->can_speak_again_at,
            ]),
            200
        );
    }

    public function postPublicMessage(Request $request) {
        $x         = 0;
        $y         = 0;
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
                case 'Shadow Plane':
                    $mapName = 'SHP';
                    break;
                case 'Hell':
                    $mapName = 'HELL';
                    break;
                case 'Purgatory':
                    $mapName = 'PURG';
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

        if ($request->has('custom_message')) {
            broadcast(new ServerMessageEvent(auth()->user(), $request->custom_message));
        } else {
            broadcast(new ServerMessageEvent(auth()->user(), $this->serverMessage->build($request->type)));
        }

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
            $this->npcCommandService->handleNPC($user->character, $npc, $request->message);

            return response()->json([], 200);
        }

        broadcast(new ServerMessageEvent($user, $this->serverMessage->build('no_matching_user')));


        return response()->json([], 200);
    }

    public function publicEntity(PublicEntityRequest $request, PctService $pctService) {
        $user = auth()->user();

        if (!$user->character->can_move || !$user->character->can_adventure || $user->character->is_dead) {
            broadcast(new ServerMessageEvent($user, 'You are to preoccupied to do this. (You must be able to move and cannot be dead).'));
            return response()->json([], 200);
        }

        if ($request->attempt_to_teleport) {
            $hasItem = $user->character->inventory->slots->filter(function ($slot) {
                if ($slot->item->type === 'quest' && !is_null($slot->item->effect)) {
                    return (new ItemEffectsValue($slot->item->effect))->teleportToCelestial();
                }
            })->isNotEmpty();

            if ($hasItem) {
                $success = $pctService->usePCT($user->character, $request->attempt_to_teleport);
            } else {
                broadcast(new ServerMessageEvent($user, 'You are missing a quest item to do that, child.'));

                return response()->json([], 200);
            }
        } else {
            $success = $pctService->usePCT($user->character, false);
        }

        if (!$success) {
            broadcast(new ServerMessageEvent($user, 'There are no celestials in the world right now, child!'));

            return response()->json([], 200);
        }

        return response()->json([], 200);
    }
}
