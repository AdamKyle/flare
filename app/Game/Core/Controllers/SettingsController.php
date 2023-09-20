<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\User;
use App\Flare\Models\Quest;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Values\FeatureTypes;
use App\Http\Controllers\Controller;
use App\Flare\Models\QuestsCompleted;
use App\Game\Core\Requests\CosmeticTextRequest;
use App\Flare\Events\UpdateCharacterAttackEvent;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index(User $user) {

        $quest              = Quest::where('unlocks_feature', FeatureTypes::COSMETIC_TEXT)->first()->id;
        $canUseCosmeticText = false;

        if (is_null($quest)) {
            $canUseCosmeticText = !is_null(QuestsCompleted::where('character_id', $user->character->id)->where('id', $quest->id)->first());
        }

        return view('game.core.settings.settings', [
            'user'         => $user,
            'races'        => GameRace::pluck('name', 'id'),
            'classes'      => GameClass::whereNull('primary_required_class_id')
                ->whereNull('secondary_required_class_id')
                ->pluck('name', 'id'),
            'cosmeticText' => $canUseCosmeticText,
        ]);
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

            Cache::put('user-show-guide-initial-message-' . $user->id, 'true');
        }

        return redirect()->back()->with('success', 'Updated character guide setting.');
    }

    public function cosmeticText(CosmeticTextRequest $request, User $user) {
        $user->update($request->all());

        return redirect()->back()->with('success', 'Updated Cosmetic Text options');
    }
}
