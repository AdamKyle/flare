<?php

namespace App\Game\Character\CharacterSheet\Controllers\Api;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterStatDetailsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Core\Events\GlobalTimeOut;
use App\Game\Core\Jobs\EndGlobalTimeOut;
use App\Game\Core\Requests\StatDetailsRequest;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Events\Values\EventType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class CharacterSheetController extends Controller
{
    private Manager $manager;

    private StatModifierDetails $statModifierDetails;

    public function __construct(Manager $manager, StatModifierDetails $statModifierDetails)
    {
        $this->manager = $manager;
        $this->statModifierDetails = $statModifierDetails;
    }

    public function sheet(Character $character, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer)
    {
        $character = new Item($character, $characterSheetBaseInfoTransformer);
        $sheet = $this->manager->createData($character)->toArray();

        return response()->json($sheet);
    }

    public function statDetails(Character $character, CharacterStatDetailsTransformer $characterStatDetailsTransformer)
    {
        $character = new Item($character, $characterStatDetailsTransformer);
        $details = $this->manager->createData($character)->toArray();

        return response()->json([
            'stat_details' => $details,
        ], 200);
    }

    public function statBreakDown(StatDetailsRequest $request, Character $character)
    {
        $breakDownDetails = $this->statModifierDetails->setCharacter($character)->forStat($request->stat_type);

        return response()->json([
            'break_down' => $breakDownDetails,
        ]);
    }

    public function basicLocationInformation(Character $character)
    {
        return response()->json([
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'gold' => $character->gold,
        ]);
    }

    public function nameChange(Request $request, Character $character)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:5', 'max:15', 'unique:characters', 'regex:/^[a-z\d]+$/i', 'unique:characters'],
        ]);

        $character->update([
            'name' => $request->name,
            'force_name_change' => false,
        ]);

        $adminUser = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', 'Admin');
        })->first();

        broadcast(new UpdateAdminChatEvent($adminUser));

        return response()->json([], 200);
    }

    public function globalTimeOut()
    {
        $timeout = now()->addMinutes(2);
        $user = auth()->user();

        $user->update([
            'timeout_until' => $timeout,
        ]);

        EndGlobalTimeOut::dispatch($user)->delay($timeout);

        event(new GlobalTimeOut($user, true));

        return response()->json();
    }

    public function activeBoons(Character $character, UsableItemTransformer $usableItemTransformer, Manager $manager)
    {
        $characterBoons = $character->boons->load('itemUsed');

        $characterBoons = $characterBoons->transform(function ($boon) use ($usableItemTransformer) {
            $item = new Item($boon->itemUsed, $usableItemTransformer);
            $item = (new Manager)->createData($item)->toArray();

            $item = $item['data'];
            $item['name'] = $boon->itemUsed->name;

            $boon->boon_applied = $item;

            return $boon;
        });

        return response()->json([
            'active_boons' => $characterBoons,
        ]);
    }

    public function automations(Character $character)
    {
        return response()->json([
            'automations' => $character->currentAutomations,
        ], 200);
    }

    public function factions(Character $character)
    {
        $winterEvent = Event::where('type', EventType::WINTER_EVENT)->first();

        $delusionalEvent = Event::where('type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

        $removeGameMaps = [];

        if (is_null($winterEvent)) {

            $gameMap = GameMap::where('only_during_event_type', EventType::WINTER_EVENT)->first();

            $removeGameMaps[] = $gameMap->id;
        }

        if (is_null($delusionalEvent)) {

            $gameMap = GameMap::where('only_during_event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

            $removeGameMaps[] = $gameMap->id;
        }

        $factions = $character->factions()->whereNotIn('game_map_id', $removeGameMaps)->get();

        $factions = $factions->transform(function ($faction) {
            $faction->map_name = $faction->gameMap->name;

            return $faction;
        });

        return response()->json([
            'factions' => array_values($factions->toArray()),
        ]);
    }

    public function skills(Character $character, CharacterPassiveSkills $characterPassiveSkills, SkillsTransformer $skillsTransformer)
    {

        $skills = new Collection($character->skills, $skillsTransformer);
        $skills = $this->manager->createData($skills)->toArray();

        return response()->json([
            'skills' => $skills,
            'passives' => $characterPassiveSkills->getPassiveSkills($character),
        ], 200);
    }

    public function baseInventoryInfo(Character $character)
    {
        return response()->json([
            'inventory_info' => [
                'gold' => number_format($character->gold),
                'gold_dust' => number_format($character->gold_dust),
                'shards' => number_format($character->shards),
                'copper_coins' => number_format($character->copper_coins),
                'inventory_used' => $character->getInventoryCount(),
                'inventory_max' => $character->inventory_max,
                'damage_stat' => $character->damage_stat,
                'to_hit_stat' => $character->class->to_hit_stat,
            ],
        ], 200);
    }

    public function cancelBoon(Character $character, CharacterBoon $boon, UseItemService $useItemService, UsableItemTransformer $usableItemTransformer, Manager $manager)
    {
        if ($character->id !== $boon->character_id) {
            return response()->json(['message' => 'You cannot do that.'], 422);
        }

        $useItemService->removeBoon($character, $boon);

        $character = $character->refresh();

        $characterBoons = $character->boons->load('itemUsed');

        $characterBoons = $characterBoons->transform(function ($boon) use ($usableItemTransformer) {
            $item = new Item($boon->itemUsed, $usableItemTransformer);
            $item = (new Manager)->createData($item)->toArray();

            $item = $item['data'];
            $item['name'] = $boon->itemUsed->name;

            $boon->boon_applied = $item;

            return $boon;
        });

        return response()->json(['message' => 'Boon has been deleted', 'boons' => $characterBoons], 200);
    }
}
