<?php

namespace App\Http\Controllers;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\Adventure;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use Cache;
use Illuminate\Http\Request;
use Storage;

class AccountDeletionController extends Controller {

    public function deleteAccount(User $user) {
        if (auth()->user()->id !== $user->id) {
            return redirect()->back()->with('error', 'You cannot do that.');
        }

        \Auth::logout();

        AccountDeletionJob::dispatch($user)->delay(now()->addMinutes(1));

        return redirect()->to('/')->with('success', 'Account deletion underway. You will receive one last email when it\'s done.');
    }
}
