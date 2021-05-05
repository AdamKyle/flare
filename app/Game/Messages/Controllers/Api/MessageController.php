<?php

namespace App\Game\Messages\Controllers\Api;

use App\Admin\Events\UpdateAdminChatEvent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Events\PrivateMessageEvent;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Flare\Models\Character;
use App\Game\Messages\Models\Message;
use App\Flare\Models\User;
use Carbon\Carbon;

class MessageController extends Controller {

    private $serverMessage;

    public function __construct(ServerMessageBuilder $serverMessage) {
        $this->middleware('auth:api');

        $this->serverMessage = $serverMessage;
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

        if (!auth()->user()->hasRole('Admin')) {
            $character = auth()->user()->character;

            $x = $character->map->character_position_x;
            $y = $character->map->character_position_y;
        }

        $message = auth()->user()->messages()->create([
            'message'    => $request->message,
            'x_position' => $x,
            'y_position' => $y,
        ]);

        broadcast(new MessageSentEvent(auth()->user(), $message))->toOthers();

        $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

        broadcast(new UpdateAdminChatEvent($adminUser));

        return response()->json([], 200);
    }

    public function generateServerMessage(Request $request) {
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

        broadcast(new ServerMessageEvent($user, $this->serverMessage->build('no_matching_user')));


        return response()->json([], 200);
    }
}
