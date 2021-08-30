<?php

namespace App\Game\Core\Controllers\Api;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Events\GlobalTimeOut;
use App\Game\Core\Jobs\EndGlobalTimeOut;
use App\Game\Core\Services\UseItemService;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetTransformer;
use Illuminate\Http\Request;

class CharacterSheetController extends Controller {

    private $manager;

    private $characterSheetTransformer;

    public function __construct(Manager $manager, CharacterSheetTransformer $characterSheetTransformer) {

        $this->manager                   = $manager;
        $this->characterSheetTransformer = $characterSheetTransformer;
    }

    public function sheet(Character $character) {
        $character = new Item($character, $this->characterSheetTransformer);
        $sheet     = $this->manager->createData($character)->toArray();

        return response()->json([
            'sheet' => $sheet,
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

    public function activeBoons(Character $character) {
        $boons = $character->boons->toArray();

        foreach ($boons as $key => $boon) {
            $skills = GameSkill::where('type', $boon['affect_skill_type'])->pluck('name')->toArray();

            $boon['type'] = (new ItemUsabilityType($boon['type']))->getNamedValue();
            $boon['affected_skills'] = implode(', ', $skills);

            $boons[$key] = $boon;
        }

        return response()->json([
            'active_boons' => $boons,
        ]);
    }

    public function cancelBoon(Character $character, CharacterBoon $boon, UseItemService $useItemService) {
        if ($character->id !== $boon->character_id) {
            return response()->json(['message' => 'You cannot do that.'], 422);
        }

        $useItemService->removeBoon($character, $boon);

        return response()->json(['message' => 'Boon has been deleted'], 200);
    }
}
