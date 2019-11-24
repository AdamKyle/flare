<?php

namespace App\Game\Battle\Controllers\Api;

use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Events\PrivateMessageEvent;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\User;

class BattleController extends Controller {

    private $manager;

    private $character;

    public function __construct(Manager $manager, CharacterAttackTransformer $character) {
        $this->middleware('auth:api');

        $this->manager   = $manager;
        $this->character = $character;
    }

    public function index(Request $request) {
        $character = User::find($request->user_id)->character;
        $character = new Item($character, $this->character);

        return response()->json([
            'monsters'  => Monster::with('skills')->get(),
            'character' => $this->manager->createData($character)->toArray()
        ], 200);
    }
}
