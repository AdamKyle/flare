<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Admin\Jobs\GenerateTestCharacter;
use App\Admin\Jobs\RunTestSimulation;
use App\Admin\Requests\CharacterModelingTestValidation;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use DB;

class CharacterModelingController extends Controller {

    public function index() {
        $hasSnapShots = CharacterSnapShot::all()->isNotEmpty();

        return view('admin.character-modeling.index', [
            'hasSnapShots' => $hasSnapShots,
            'cardTitle'    => $hasSnapShots ? 'Modeling' : 'Generate',
            'snapShots'    => CharacterSnapShot::all()->paginate(4),
        ]);
    }

    public function fetchSheet(Character $character) {
        return view ('game.core.character.sheet', [
            'character' => $character,
            'characterInfo' => [
                'maxAttack' => $character->getInformation()->buildAttack(),
                'maxHealth' => $character->getInformation()->buildHealth(),
                'maxHeal'   => $character->getInformation()->buildHealFor(),
                'maxAC'     => $character->getInformation()->buildDefence(),
                'str'       => $character->getInformation()->statMod('str'),
                'dur'       => $character->getInformation()->statMod('dur'),
                'dex'       => $character->getInformation()->statMod('dex'),
                'chr'       => $character->getInformation()->statMod('chr'),
                'int'       => $character->getInformation()->statMod('int'),
            ],
        ]);
    }

    public function monsterData(Monster $monster) {
        return view('admin.character-modeling.monster-data', [
            'monster' => $monster,
        ]);
    }

    public function adventureData(Adventure $adventure) {
        return view('admin.character-modeling.adventure-data', [
            'adventure' => $adventure,
        ]);
    }

    public function battleResults(CharacterSnapShot $characterSnapShot) {
        
        return view('admin.character-modeling.battle-results', [
            'battleData'  => $characterSnapShot->battle_simmulation_data,
            'monsterId'   => Monster::find($characterSnapShot->battle_simmulation_data['monster_id'])->id,
            'characterId' => $characterSnapShot->character_id,
        ]);
    }

    public function adventureResults(CharacterSnapShot $characterSnapShot) {
        return view('admin.character-modeling.adventure-results', [
            'adventureData' => $characterSnapShot->adventure_simmulation_data,
            'characterId'   => $characterSnapShot->character_id,
        ]);
    }

    public function assignItem(Request $request, Character $character) {
        $request->validate(['item_id' => 'required']);

        $freeSlots = ($character->inventory_max - $character->inventory->slots()->count());
        
        if ($freeSlots === 0) {
            return redirect()->back()->with('error', "You don't have the inventory space");
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $request->item_id,
        ]);

        return redirect()->to(route('admin.character.modeling.sheet', [
            'character' => $character
        ]))->with('success', 'Gave item to character.');
    }

    public function assignAll(Request $request, Character $character) {
        $request->validate(['items' => 'required']);

        $freeSlots = ($character->inventory_max - $character->inventory->slots()->count());

        if ($freeSlots === 0) {
            return redirect()->back()->with('error', "You don't have the inventory space");
        }

        foreach ($request->items as $item) {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item
            ]);
        }

        return redirect()->to(route('admin.character.modeling.sheet', [
            'character' => $character
        ]))->with('success', 'Selected item(s) given to character.');
    }

    public function resetInventory(Character $character) {
        $slots = $character->inventory->slots;

        foreach($slots as $slot) {
            $slot->delete();
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => Item::first()->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        return redirect()->to(route('admin.character.modeling.sheet', [
            'character' => $character
        ]))->with('success', 'Character inventory reset back to default.');
    }

    public function applySnapShot(Request $request, Character $character) {
        $request->validate(['snap_shot' => 'required']);

        $foundSnapShot = CharacterSnapShot::find($request->snap_shot)->snap_shot;

        $character->update($foundSnapShot);

        return redirect()->back()->with('success', 'Applied Level: ' . $foundSnapShot['level'] . ' to character.');
    }

    public function generate() {

        if (User::where('is_test', true)->get()->isNotEmpty()) {
            return redirect()->back()->with('error', 'You already have test characters for every race and class and combination of.');
        }

        $totalGameRaces   = GameRace::count() - 1;
        $totalGameClasses = GameClass::count() - 1;

        foreach (GameRace::all() as $raceIndex => $gameRace) {
            foreach (GameClass::all() as $classIndex => $gameClass) {
                if ($totalGameRaces === $raceIndex && $totalGameClasses === $classIndex) {
                    GenerateTestCharacter::dispatch($gameRace, $gameClass, auth()->user());
                } else {
                    GenerateTestCharacter::dispatch($gameRace, $gameClass);
                }
            }
        }

        return redirect()->to(route('admin.character.modeling'))->with('success', 'Generation underway. You may leave this page. We will email you when done.');
    }

    public function test(CharacterModelingTestValidation $request) {
        if ($request->total_times > 10) {
            return redirect()->back()->with('error', 'You may only repeat this test 10 times with any one character.');
        }

        $count           = count($request->characters);
        $totalCharacters =  $count === 1 ? 1 : $count - 1;

        switch($request->type) {
            case 'monster':
                // truncate all previous battle simulation reports.
                DB::table('character_snap_shots')->update(['battle_simmulation_data' => null]);
                break;
            case 'adventure':
                DB::table('character_snap_shots')->update(['adventure_simmulation_data' => null]);
                break;
            default:
                break;
        }

        $sendEmail = false;

        foreach ($request->characters as $index => $id) {
            $character = Character::find($id);

            if (is_null($character)) {
                
                return redirect()->back()->with('error', 'Character does not exist for id: ' . $id);
            }

            $snapShot  = $character->snapShots()->where('snap_shot->level', $request->character_levels)->first();
            
            if (is_null($snapShot)) {
                return redirect()->back()->with('error', 'Level entered does not match any snap shot data for character: ' . $character->id);
            }

            $character->update($snapShot->snap_shot);

            if ($totalCharacters === 1) {
                $sendEmail = true;
            } else if ($index === $totalCharacters) {
                $sendEmail = true;
            }
            
            RunTestSimulation::dispatch($character->refresh(), $request->type, $request->model_id, $request->total_times, auth()->user(), $sendEmail, $index);
        }
        
        return redirect()->back()->with('success', 'Testing under way. You may log out, we will email you when done.');
    }
}
