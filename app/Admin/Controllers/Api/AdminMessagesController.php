<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\UserService;
use App\Game\Messages\Models\Message;
use App\Http\Controllers\Controller;
use Facades\App\Admin\Formatters\MessagesFormatter;

class AdminMessagesController extends Controller
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * MessagesController constructor.
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $messages = Message::orderByDesc('id')->take(100)->get();

        return response()->json(MessagesFormatter::format($messages)->toArray(), 200);
    }
}
