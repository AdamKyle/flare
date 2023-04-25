<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\SilenceUserRequest;
use App\Flare\Models\Raid;
use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use App\Admin\Events\UpdateAdminChatEvent;
use App\Admin\Requests\BanUserRequest;
use App\Admin\Services\UserService;
use App\Game\Messages\Models\Message;
use Facades\App\Admin\Formatters\MessagesFormatter;

class EventScheduleController extends Controller {


    public function index() {
        $raids = Raid::select('name', 'id')->get()->toArray();

        return response()->json([
            'raids' => $raids
        ]);
    }
}
