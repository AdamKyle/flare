<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Notification;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index() {
        return response()->json(auth()->user()->character->notifications()->where('read', false)->get()->toArray(), 200);
    }

    public function clear() {
        $notifications = auth()->user()->character->notifications()->where('read', false)->get();

        foreach ($notifications as $notification) {
            $notification->update([
                'read' => true,
            ]);
        }

        event(new UpdateNotificationsBroadcastEvent(auth()->user()->character->notifications()->where('read', false)->get(), auth()->user()));

        return response()->json([], 200);
    }

    public function clearNotification(Request $request, Notification $notification) {

        $character = auth()->user()->character;

        $notification = $character->notifications()->where('id', $notification->id)->where('character_id', $notification->character_id)->first();

        if (is_null($notification)) {
            return response()->json(['error' => 'Invalid input.'], 422);
        }

        $notification->update([
            'read' => true,
        ]);

        event(new UpdateNotificationsBroadcastEvent(auth()->user()->character->notifications()->where('read', false)->get(), auth()->user()));

        return response()->json([], 200);
    }
}
