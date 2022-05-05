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

class AdminMessagesController extends Controller {

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
}
