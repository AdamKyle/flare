<?php

namespace App\Game\Core\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index(User $user) {
        return view('game.core.settings.settings', [
            'user' => $user,
        ]);
    }

    public function emailSettings(Request $request, User $user) {
        $user->update([
            'adventure_email'         => $request->has('adventure_email') ? $request->adventure_email : false,
            'new_building_email'      => $request->has('new_building_email') ? $request->new_building_email : false,
            'kingdoms_update_email'   => $request->has('kingdoms_update_email') ? $request->new_building_email : false,
            'upgraded_building_email' => $request->has('upgraded_building_email') ? $request->upgraded_building_email : false,
            'rebuilt_building_email'  => $request->has('rebuilt_building_email') ? $request->rebuilt_building_email : false,
            'kingdom_attack_email'    => $request->has('kingdom_attack_email') ? $request->kingdom_attack_email : false,
            'unit_recruitment_email'  => $request->has('unit_recruitment_email') ? $request->unit_recruitment_email : false,
        ]);

        return redirect()->back()->with('success', 'Updated email preferences.');
    }

    public function chatSettings(request $request, User $user) {

        $user->update([
            'show_unit_recruitment_messages' => $request->has('show_unit_recruitment_messages') ? $request->show_unit_recruitment_messages : false,
            'show_building_upgrade_messages' => $request->has('show_building_upgrade_messages') ? $request->show_building_upgrade_messages : false,
            'show_kingdom_update_messages'   => $request->has('show_kingdom_update_messages') ? $request->show_kingdom_update_messages : false,
            'show_building_rebuilt_messages' => $request->has('show_building_rebuilt_messages') ? $request->show_building_rebuilt_messages : false,
        ]);

        return redirect()->back()->with('success', 'Updated chat preferences.');
    }

    public function characterSettings(Request $request, User $user) {
        $request->validate([
            'name' => 'required|string|max:15|min:5|unique:characters|regex:/^[a-zA-Z0-9]+$/',
        ]);

        $user->character->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Updated character name.');
    }
}
