<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\SilenceUserRequest;
use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use App\Admin\Events\UpdateAdminChatEvent;
use App\Admin\Requests\BanUserRequest;
use App\Admin\Services\UserService;
use App\Game\Messages\Models\Message;
use Facades\App\Admin\Formatters\MessagesFormatter;

class MessagesController extends Controller {

    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * MessagesController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function index() {
        $messages = Message::orderByDesc('id')->take(100)->get();

        return response()->json(MessagesFormatter::format($messages)->toArray(), 200);
    }

    public function banUser(BanUserRequest $request) {
        $user = User::find($request->user_id);

        $unBanAt = null;

        if ($request->ban_for !== 'perm') {
            $unBanAt = $this->userService->fetchUnBanAt($user, $request->ban_for);

            if (is_null($unBanAt)) {
                return response()->json([
                    'message' => 'Invalid input for ban length.',
                ]);
            }
        } else {
            $this->userService->broadCastAdminMessage($user);
        }

        $user->update([
            'is_banned'     => true,
            'unbanned_at'   => $unBanAt,
            'banned_reason' => $request->ban_message,
        ]);

        $user = $user->refresh();

        $this->userService->sendUserMail($user, $unBanAt);

        broadcast(new UpdateAdminChatEvent(auth()->user()));

        return response()->json([], 200);
    }

    public function silenceUser(SilenceUserRequest $request) {
        $user = User::find($request->user_id);

        $this->userService->silence($user, $request->for);

        return response()->json([], 200);
    }

    public function forceNameChange(User $user) {
        $this->userService->forceNameChange($user);

        return response()->json([], 200);
    }
}
