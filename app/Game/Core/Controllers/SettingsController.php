<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use App\Flare\Values\FeatureTypes;
use App\Flare\Values\NameTags;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Core\Requests\CosmeticTextRequest;
use App\Game\Core\Requests\NameTagRequest;
use App\Game\Core\Requests\RaceChangerRequest;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __construct(private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler)
    {
        $this->middleware('auth');
    }

    public function index(User $user)
    {

        $canUseCosmeticText = $user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_TEXT)->count() > 0;
        $canUseNameTags = $user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_NAME_TAGS)->count() > 0;
        $canUseCosmeticRaceChanger = $user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_RACE_CHANGER)->count() > 0;

        return view('game.core.settings.settings', [
            'user' => $user,
            'races' => GameRace::pluck('name', 'id'),
            'classes' => GameClass::whereNull('primary_required_class_id')
                ->whereNull('secondary_required_class_id')
                ->pluck('name', 'id'),
            'cosmeticText' => $canUseCosmeticText,
            'cosmeticNameTag' => $canUseNameTags,
            'cosmeticRaceChanger' => $canUseCosmeticRaceChanger,
            'nameTags' => NameTags::$valueNames,
        ]);
    }

    public function chatSettings(request $request, User $user)
    {

        $user->update([
            'show_unit_recruitment_messages' => $request->has('show_unit_recruitment_messages') ? $request->show_unit_recruitment_messages : false,
            'show_building_upgrade_messages' => $request->has('show_building_upgrade_messages') ? $request->show_building_upgrade_messages : false,
            'show_kingdom_update_messages' => $request->has('show_kingdom_update_messages') ? $request->show_kingdom_update_messages : false,
            'show_building_rebuilt_messages' => $request->has('show_building_rebuilt_messages') ? $request->show_building_rebuilt_messages : false,
            'show_monster_to_low_level_message' => $request->has('show_monster_to_low_level_message') ? $request->show_monster_to_low_level_message : false,
            'show_xp_for_exploration' => $request->has('show_xp_for_exploration') ? $request->show_xp_for_exploration : false,
            'show_xp_per_kill' => $request->has('show_xp_per_kill') ? $request->show_xp_per_kill : false,
            'show_xp_for_class_masteries' => $request->has('show_xp_for_class_masteries') ? $request->show_xp_for_class_masteries : false,
            'show_xp_for_class_ranks' => $request->has('show_xp_for_class_ranks') ? $request->show_xp_for_class_ranks : false,
            'show_xp_for_equipped_class_specials' => $request->has('show_xp_for_equipped_class_specials') ? $request->show_xp_for_equipped_class_specials : false,
            'show_faction_loyalty_xp_gain' => $request->has('show_faction_loyalty_xp_gain') ? $request->show_faction_loyalty_xp_gain : false,
            'show_skill_xp_per_kill' => $request->has('show_skill_xp_per_kill') ? $request->show_skill_xp_per_kill : false,
            'show_item_skill_kill_count' => $request->has('show_item_skill_kill_count') ? $request->show_item_skill_kill_count : false,
            'show_gold_per_kill' => $request->has('show_gold_per_kill') ? $request->show_gold_per_kill : false,
            'show_gold_dust_per_kill' => $request->has('show_gold_dust_per_kill') ? $request->show_gold_dust_per_kill : false,
            'show_shards_per_kill' => $request->has('show_shards_per_kill') ? $request->show_shards_per_kill : false,
            'show_copper_coins_per_kill' => $request->has('show_copper_coins_per_kill') ? $request->show_copper_coins_per_kill : false,
            'show_faction_point_message' => $request->has('show_faction_point_message') ? $request->show_faction_point_message : false,
        ]);

        return redirect()->back()->with('success', 'Updated chat preferences.');
    }

    public function autoDisenchantSettings(Request $request, User $user)
    {
        if (filter_var($request->auto_disenchant, FILTER_VALIDATE_BOOLEAN) === false) {
            $request->merge([
                'auto_disenchant' => false,
                'auto_sell_item' => false,
                'auto_disenchant_amount' => null,
            ]);
        } elseif (is_null($request->auto_disenchant_amount)) {
            return redirect()->back()->with('error', 'You must select an disenchant amount.');
        }

        $user->update([
            'auto_disenchant' => filter_var($request->auto_disenchant, FILTER_VALIDATE_BOOLEAN),
            'auto_disenchant_amount' => $request->has('auto_disenchant_amount') ? ($request->auto_disenchant_amount !== '' ? $request->auto_disenchant_amount : null) : null,
            'auto_sell_item' => $request->has('auto_sell_item') ? $request->auto_sell_item : false,
        ]);

        return redirect()->back()->with('success', 'Updated auto disenchant preferences.');
    }

    public function disableAttackTypePopOvers(request $request, User $user)
    {

        $user->update([
            'disable_attack_type_popover' => $request->has('disable_attack_type_popover') ? $request->disable_attack_type_popover : false,
        ]);

        event(new UpdateCharacterAttackEvent($user->refresh()->character));

        return redirect()->back()->with('success', 'Updated Attack Popover Preferences');
    }

    public function characterSettings(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:15|min:5|unique:characters|regex:/^[a-zA-Z0-9]+$/',
        ]);

        $user->character->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Updated character name.');
    }

    public function guideSettings(Request $request, User $user)
    {

        if (filter_var($request->guide_enabled, FILTER_VALIDATE_BOOLEAN)) {
            $user->update([
                'guide_enabled' => $request->guide_enabled,
            ]);

            Cache::put('user-show-guide-initial-message-'.$user->id, 'true');
        }

        return redirect()->back()->with('success', 'Updated character guide setting.');
    }

    public function cosmeticText(CosmeticTextRequest $request, User $user)
    {

        if ($user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_TEXT)->count() <= 0) {
            return redirect()->back()->with('error', 'Missing required quest completion for that action.');
        }

        $user->update($request->all());

        return redirect()->back()->with('success', 'Updated Cosmetic Text options');
    }

    public function cosmeticNametag(NameTagRequest $request, User $user)
    {

        if ($user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_NAME_TAGS)->count() <= 0) {
            return redirect()->back()->with('error', 'Missing required quest completion for that action.');
        }

        $nameTag = $request->name_tag;

        try {
            (new NameTags($nameTag));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'No such name tag.');
        }

        $user->update($request->all());

        return redirect()->back()->with('success', 'Updated Name Tag options');
    }

    public function cosmeticRaceChanger(RaceChangerRequest $request, User $user)
    {

        if ($user->character->questsCompleted->where('quest.unlocks_feature', FeatureTypes::COSMETIC_RACE_CHANGER)->count() <= 0) {
            return redirect()->back()->with('error', 'Missing required quest completion for that action.');
        }

        $character = $user->character;

        $stats = ['str', 'dex', 'chr', 'int', 'agi', 'dur', 'focus'];

        foreach ($stats as $stat) {
            if ($character->race->{$stat.'_mod'} > 0) {
                $character->{$stat} -= $character->race->{$stat.'_mod'};
            }
        }

        $character->save();

        $character = $character->refresh();

        $gameRace = GameRace::find($request->race_id);

        foreach ($stats as $stat) {
            if ($gameRace->{$stat.'_mod'} > 0) {
                $character->{$stat} += $gameRace->{$stat.'_mod'};
            }
        }

        $character->game_race_id = $gameRace->id;

        $character->save();

        $character = $character->refresh();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        return redirect()->back()->with('success', 'Your race has been changed!');
    }
}
