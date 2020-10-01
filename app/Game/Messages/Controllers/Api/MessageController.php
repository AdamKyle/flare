<?php

namespace App\Game\Messages\Controllers\Api;

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

    public function postPublicMessage(Request $request) {
        $message = auth()->user()->messages()->create([
            'message' => $request->message,
        ]);

        broadcast(new MessageSentEvent(auth()->user(), $message))->toOthers();

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
            return response()->json([], 200);
        }

        broadcast(new ServerMessageEvent($user, $this->serverMessage->build('no_matching_user')));
        return response()->json([], 200);
    }
}
