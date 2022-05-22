<?php

namespace App\Game\Core\Controllers;

use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
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
            'user'    => $user,
            'races'   => GameRace::pluck('name', 'id'),
            'classes' => GameClass::pluck('name', 'id'),
        ]);
    }

    public function emailSettings(Request $request, User $user) {
        $user->update([
            'adventure_email'         => $request->has('adventure_email') ? $request->adventure_email : false,
            'upgraded_building_email' => $request->has('upgraded_building_email') ? $request->upgraded_building_email : false,
            'rebuilt_building_email'  => $request->has('rebuilt_building_email') ? $request->rebuilt_building_email : false,
            'kingdom_attack_email'    => $request->has('kingdom_attack_email') ? $request->kingdom_attack_email : false,
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

    public function autoDisenchantSettings(request $request, User $user) {

        if ($request->has('auto_disenchant')) {
            if (is_null($request->auto_disenchant_amount)) {
                return redirect()->back()->with('error', 'You must select to either disenchant all items that drop or only those under 1 Billion gold.');
            }
        }

        $user->update([
            'auto_disenchant' => $request->has('auto_disenchant') ? $request->auto_disenchant : false,
            'auto_disenchant_amount' => $request->has('auto_disenchant_amount') ? ($request->auto_disenchant_amount !== "" ? $request->auto_disenchant_amount : null) : null,
        ]);

        return redirect()->back()->with('success', 'Updated auto disenchant preferences.');
    }

    public function disableAttackTypePopOvers(request $request, User $user) {

        $user->update([
            'disable_attack_type_popover' => $request->has('disable_attack_type_popover') ? $request->disable_attack_type_popover : false,
        ]);

        event(new UpdateCharacterAttackEvent($user->refresh()->character));

        return redirect()->back()->with('success', 'Updated Attack Popover Preferences');
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

    public function guideSettings(Request $request, User $user) {

        if (filter_var($request->guide_enabled, FILTER_VALIDATE_BOOLEAN)) {
            $user->update([
                'guide_enabled' => $request->guide_enabled,
            ]);
        }

        return redirect()->back()->with('success', 'Updated character guide setting.');
    }
}
