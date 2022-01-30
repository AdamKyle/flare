<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateQueenOfHeartsPanel;
use App\Game\Core\Requests\MoveRandomEnchantment;
use App\Game\Core\Requests\PurchaseRandomEnchantment;
use App\Game\Core\Requests\ReRollRandomEnchantment;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Services\ReRollEnchantmentService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;

class ServerTimeController extends Controller {

    public function serverTime() {
        return response()->json([
            'server_time' => now()->setTimezone(config('app.timezone'))
        ], 200);
    }
}
