<?php

namespace App\Admin\Controllers;

use App\Admin\Jobs\GenerateTestCharacter;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;

class CharacterModelingController extends Controller {

    public function index() {
        $hasSnapShots = User::where('users.is_test', true)->get()->isNotEmpty();

        return view('admin.character-modeling.index', [
            'hasSnapShots' => $hasSnapShots,
            'cardTitle'    => $hasSnapShots ? 'Modeling' : 'Generate',
        ]);
    }

    public function generate() {
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

        return redirect()->back()->with('success', 'Generation underway. You may leave this page. We will email you when done.');
    }
}
