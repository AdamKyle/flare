<?php

namespace App\Game\Core\Controllers\Api;

use App\Admin\Events\UpdateAdminChatEvent;

use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterPassiveSkills;
use Illuminate\Http\Request;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Models\Character;
use App\Game\Core\Jobs\EndGlobalTimeOut;
use App\Game\Core\Services\UseItemService;

class CharacterSheetController extends Controller {

    private $manager;

    private $characterTopBarTransformer;

    public function __construct(Manager $manager, CharacterTopBarTransformer $characterTopBarTransformer) {

        $this->manager                    = $manager;
        $this->characterTopBarTransformer = $characterTopBarTransformer;
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

        auth()->user()->update([
            'timeout_until' => $timeout,
        ]);

        EndGlobalTimeOut::dispatch(auth()->user())->delay($timeout);

        return response()->json([
            'timeout_until' => $timeout,
        ]);
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

    public function cancelBoon(Character $character, CharacterBoon $boon, UseItemService $useItemService) {
        if ($character->id !== $boon->character_id) {
            return response()->json(['message' => 'You cannot do that.'], 422);
        }

        $useItemService->removeBoon($character, $boon);

        return response()->json(['message' => 'Boon has been deleted'], 200);
    }
}
