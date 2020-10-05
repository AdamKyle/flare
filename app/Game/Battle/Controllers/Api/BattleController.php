<?php

namespace App\Game\Battle\Controllers\Api;

use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Flare\Models\User;

class BattleController extends Controller {

    private $manager;

    private $character;

    public function __construct(Manager $manager, CharacterAttackTransformer $character) {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead')->except(['revive', 'index']);
        $this->middleware('is.character.adventuring')->except(['index']);

        $this->manager   = $manager;
        $this->character = $character;
    }

    public function index(Request $request) {
        $character = User::find($request->user_id)->character;
        $character = new Item($character, $this->character);

        return response()->json([
            'monsters'  => Monster::with('skills')->orderBy('max_level', 'asc')->get(),
            'character' => $this->manager->createData($character)->toArray()
        ], 200);
    }

    public function battleResults(Request $request, Character $character) {
        if ($request->is_character_dead) {

            $character->update(['is_dead' => true]);

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'dead_character'));
            event(new AttackTimeOutEvent($character));
            event(new CharacterIsDeadBroadcastEvent($character->user, true));
            event(new UpdateTopBarEvent($character));

            return response()->json([], 200);
        }

        if ($request->is_defender_dead) {

            switch ($request->defender_type) {
                case 'monster':
                    $monster = Monster::find($request->monster_id);

                    event(new UpdateCharacterEvent($character, $monster));
                    event(new DropsCheckEvent($character, $monster));
                    event(new GoldRushCheckEvent($character, $monster));
                    event(new AttackTimeOutEvent($character));
                    break;
                default:
                    return response()->json([
                        'message' => 'Could not find type of defender.'
                    ], 422);
            }
        }

        return response()->json([], 200);
    }

    public function revive(Request $request, Character $character) {
        $character->update([
            'is_dead' => false
        ]);

        event(new CharacterIsDeadBroadcastEvent($character->user));
        event(new UpdateTopBarEvent($character));

        $character = new Item($character, $this->character);

        return response()->json([
            'character' => $this->manager->createData($character)->toArray()
        ], 200);
    }
}
