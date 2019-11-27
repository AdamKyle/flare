<?php

namespace App\Game\Battle\Controllers\Api;

use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Jobs\AttackTimeOut;
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

    public function battleResults(Request $request, Character $character) {
        if ($request->is_character_dead) {
            event(new ServerMessageEvent($character->user, 'dead_character'));

            return response()->json([], 200);
        }

        if ($request->is_defender_dead) {

            switch ($request->defender_type) {
                case 'monster':
                    $monster = Monster::find($request->monster_id);

                    event(new UpdateCharacterEvent($character, $monster));
                    event(new DropsCheckEvent($character, $monster));
                    event(new GoldRushCheckEvent($character, $monster));
                    event(new AttackTimeOutEvent($character, $monster));

                    break;
                case 'beast':
                    break;
                case 'player':
                    break;
                default:
                    return response()->json([
                        'message' => 'Could not find type of defender.'
                    ], 422);
            }
        }
    }
}
