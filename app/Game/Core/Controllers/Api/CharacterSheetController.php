<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Game\CharacterInventory\Services\UseItemService;
use App\Game\Events\Values\EventType;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use App\Flare\Models\Character;
use League\Fractal\Resource\Item;
use App\Flare\Models\CharacterBoon;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\GlobalTimeOut;
use League\Fractal\Resource\Collection;
use App\Game\Core\Jobs\EndGlobalTimeOut;
use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Transformers\CharacterElementalAtonementTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Flare\Transformers\CharacterStatDetailsTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterResistanceInfoTransformer;
use App\Flare\Transformers\CharacterReincarnationInfoTransformer;

class CharacterSheetController extends Controller {

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager) {
        $this->manager = $manager;
    }

    public function sheet(Character $character, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {
        $character = new Item($character, $characterSheetBaseInfoTransformer);
        $sheet     = $this->manager->createData($character)->toArray();

        return response()->json([
            'sheet' => $sheet,
        ], 200);
    }

    public function baseCharacterInformation(Character $character, CharacterSheetBaseInfoTransformer $characterBaseInfo) {
        $character = new Item($character, $characterBaseInfo);
        $details   = $this->manager->createData($character)->toArray();

        return response()->json([
            'base_info' => $details,
        ], 200);
    }

    public function statDetails(Character $character, CharacterStatDetailsTransformer $characterStatDetailsTransformer) {
        $character = new Item($character, $characterStatDetailsTransformer);
        $details   = $this->manager->createData($character)->toArray();

        return response()->json([
            'stat_details' => $details,
        ], 200);
    }

    public function resistanceInfo(Character $character, CharacterResistanceInfoTransformer $characterResistanceInfoTransformer) {
        $character = new Item($character, $characterResistanceInfoTransformer);
        $details   = $this->manager->createData($character)->toArray();

        return response()->json([
            'resistance_info' => $details,
        ], 200);
    }

    public function reincarnationInfo(Character $character, CharacterReincarnationInfoTransformer $characterReincarnationInfoTransformer) {
        $character = new Item($character, $characterReincarnationInfoTransformer);
        $details   = $this->manager->createData($character)->toArray();

        return response()->json([
            'reincarnation_details' => $details,
        ], 200);
    }

    public function elementalAtonementInfo(Character $character, CharacterElementalAtonementTransformer $characterElementalAtonementTransformer) {
        $character = new Item($character, $characterElementalAtonementTransformer);
        $details   = $this->manager->createData($character)->toArray();

        return response()->json([
            'elemental_atonement_details' => $details,
        ], 200);
    }

    public function basicLocationInformation(Character $character) {
        return response()->json([
            'x_position'    => $character->map->character_position_x,
            'y_position'    => $character->map->character_position_y,
            'gold'          => $character->gold,
        ]);
    }

    public function nameChange(Request $request, Character $character) {
        $request->validate([
            'name' => ['required', 'string', 'min:5', 'max:15', 'unique:characters', 'regex:/^[a-z\d]+$/i', 'unique:characters'],
        ]);

        $character->update([
            'name'              => $request->name,
            'force_name_change' => false,
        ]);

        $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

        broadcast(new UpdateAdminChatEvent($adminUser));

        return response()->json([], 200);
    }

    public function globalTimeOut() {
        $timeout = now()->addMinutes(2);
        $user    = auth()->user();

        $user->update([
            'timeout_until' => $timeout,
        ]);

        EndGlobalTimeOut::dispatch($user)->delay($timeout);

        event(new GlobalTimeOut($user, true));

        return response()->json();
    }

    public function activeBoons(Character $character, UsableItemTransformer $usableItemTransformer,  Manager $manager) {
        $characterBoons = $character->boons->load('itemUsed');

        $characterBoons = $characterBoons->transform(function($boon) use($usableItemTransformer, $manager) {
            $item = new Item($boon->itemUsed, $usableItemTransformer);
            $item = (new Manager())->createData($item)->toArray();

            $item         = $item['data'];
            $item['name'] = $boon->itemUsed->name;

            $boon->boon_applied = $item;

            return $boon;
        });

        return response()->json([
            'active_boons' => $characterBoons
        ]);
    }

    public function automations(Character $character) {
        return response()->json([
            'automations' => $character->currentAutomations
        ], 200);
    }

    public function factions(Character $character) {
        $factions = $character->factions->transform(function($faction) {
            $faction->map_name = $faction->gameMap->name;

            return $faction;
        });

        $winterEvent = Event::where('type', EventType::WINTER_EVENT)->first();

        if (is_null($winterEvent)) {

            $gameMap = GameMap::where('only_during_event_type', EventType::WINTER_EVENT)->first();

            $factions = $factions->filter(function($faction) use($gameMap) {
                return $faction->game_map_id !== $gameMap->id;
            });
        }

        return response()->json([
            'factions' => $factions,
        ], 200);
    }

    public function skills(Character $character, CharacterPassiveSkills $characterPassiveSkills, SkillsTransformer $skillsTransformer) {

        $skills = new Collection($character->skills, $skillsTransformer);
        $skills = $this->manager->createData($skills)->toArray();

        return response()->json([
            'skills'   => $skills,
            'passives' => $characterPassiveSkills->getPassiveSkills($character),
        ], 200);
    }

    public function baseInventoryInfo(Character $character) {
        return response()->json([
            'inventory_info' => [
                'gold'           => number_format($character->gold),
                'gold_dust'      => number_format($character->gold_dust),
                'shards'         => number_format($character->shards),
                'copper_coins'   => number_format($character->copper_coins),
                'inventory_used' => $character->getInventoryCount(),
                'inventory_max'  => $character->inventory_max,
                'damage_stat'    => $character->damage_stat,
                'to_hit_stat'    => $character->class->to_hit_stat,
            ],
        ], 200);
    }

    public function cancelBoon(Character $character, CharacterBoon $boon, UseItemService $useItemService, UsableItemTransformer $usableItemTransformer,  Manager $manager) {
        if ($character->id !== $boon->character_id) {
            return response()->json(['message' => 'You cannot do that.'], 422);
        }

        $useItemService->removeBoon($character, $boon);

        $character = $character->refresh();

        $characterBoons = $character->boons->load('itemUsed');

        $characterBoons = $characterBoons->transform(function($boon) use($usableItemTransformer, $manager) {
            $item = new Item($boon->itemUsed, $usableItemTransformer);
            $item = (new Manager())->createData($item)->toArray();

            $item         = $item['data'];
            $item['name'] = $boon->itemUsed->name;

            $boon->boon_applied = $item;

            return $boon;
        });

        return response()->json(['message' => 'Boon has been deleted', 'boons' => $characterBoons], 200);
    }
}
