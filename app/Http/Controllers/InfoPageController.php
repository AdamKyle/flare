<?php

namespace App\Http\Controllers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Traits\Controllers\MonstersShowInformation;
use App\Flare\Values\ItemEffectsValue;
use Storage;
use Illuminate\Http\Request;
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
use App\Flare\Traits\Controllers\ItemsShowInformation;

class InfoPageController extends Controller
{

    use ItemsShowInformation, MonstersShowInformation;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function viewPage(Request $request, string $pageName)
    {
        $files = Storage::disk('info')->files($pageName);

        if (empty($files)) {
            abort(404);
        }

        if (is_null(config('info.' . $pageName))) {
            abort(404);
        }

        $sections = [];

        $files = $this->cleanFiles($files);

        for ($i = 0; $i < count($files); $i++) {
            if (explode('.', $files[$i])[1] === 'md') {
                $view          = null;
                $livewire      = false;
                $only          = null;
                $index         = $i === 0 ? 0 : $i;
                $before        = null;
                $showSkillInfo = false;
                $showDropDown  = false;
                $type          = null;
                $craftOnly     = false;

                if (isset(config('info.' . $pageName)[$index])) {
                    $view      = config('info.' . $pageName)[$index]['view'];
                    $livewire  = config('info.' . $pageName)[$index]['livewire'];
                    $only      = config('info.' . $pageName)[$index]['only'];
                    $before    = config('info.' . $pageName)[$index]['insert_before_table'];
                    $type      = config('info.' . $pageName)[$index]['type'];
                    $craftOnly = config('info.' . $pageName)[$index]['craft_only'];

                    if (isset(config('info.' . $pageName)[$index]['showSkillInfo'])) {
                        $showSkillInfo = config('info.' . $pageName)[$index]['showSkillInfo'];
                    }

                    if (isset(config('info.' . $pageName)[$index]['showDropDown'])) {
                        $showDropDown = config('info.' . $pageName)[$index]['showDropDown'];
                    }
                }

                $sections[] = [
                    'content'       => Storage::disk('info')->get($files[$i]),
                    'view'          => $view,
                    'livewire'      => $livewire,
                    'only'          => $only,
                    'type'          => $type,
                    'craftOnly'     => $craftOnly,
                    'before'        => $before,
                    'showSkillInfo' => $showSkillInfo,
                    'showDropDown'  => $showDropDown,
                ];
            }
        }

        return view('information.core', [
            'pageTitle' => $pageName,
            'sections'  => $sections,
        ]);
    }

    public function viewRace(Request $request, GameRace $race) {
        return view('information.races.race', [
            'race' => $race,
        ]);
    }

    public function viewClass(Request $request, GameClass $class) {
        return view('information.classes.class', [
            'class' => $class,
        ]);
    }

    public function viewMap(GameMap $map) {
        
        $effects = match ($map->name) {
            'Labyrinth' => ItemEffectsValue::LABYRINTH,
            'Dungeons' => ItemEffectsValue::DUNGEON,
            default => '',
        };

        return view('information.maps.map', [
            'map' => $map,
            'itemNeeded' => Item::where('effect', $effects)->first(),
            'mapUrl' => Storage::disk('maps')->url($map->path),
        ]);
    }

    public function viewSkill(Request $request, GameSkill $skill) {
        return view('information.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function viewAdventure(Request $request, Adventure $adventure) {

        return view('information.adventures.adventure', [
            'adventure' => $adventure,
        ]);
    }

    public function viewMonster(Request $request, Monster $monster) {
        return $this->renderMonsterShow($monster, 'information.monsters.monster');
    }

    public function viewLocation(Request $request, Location $location) {
        return view('information.locations.location', [
            'location' => $location
        ]);
    }

    public function viewUnit(Request $request, GameUnit $unit) {
        $belongsToKingdomBuilding = GameBuildingUnit::where('game_unit_id', $unit->id)->first();

        if (!is_null($belongsToKingdomBuilding)) {
            $belongsToKingdomBuilding = $belongsToKingdomBuilding->gameBuilding;
        }

        return view('information.units.unit', [
            'unit'          => $unit,
            'building'      => $belongsToKingdomBuilding,
            'requiredLevel' => GameBuildingUnit::where('game_building_id', $belongsToKingdomBuilding->id)
                                               ->where('game_unit_id', $unit->id)
                                               ->first()->required_level
        ]);
    }

    public function viewItem(Request $request, Item $item) {
        return $this->renderItemShow('information.items.item', $item);
    }

    public function viewAffix(Request $request, ItemAffix $affix) {
        return view('information.affixes.affix', [
            'itemAffix' => $affix
        ]);
    }

    public function viewNpc(Npc $npc) {
        return view('information.npcs.npc', [
            'npc' => $npc
        ]);
    }

    public function viewQuest(Quest $quest) {
        $skill = null;

        if ($quest->unlocks_skill) {
            $skill = GameSkill::where('type', $quest->unlocks_skill_type)->where('is_locked', true)->first();
        }

        return view('information.quests.quest', [
            'quest'       => $quest,
            'lockedSkill' => $skill,
        ]);
    }

    protected function cleanFiles(array $files): array {
        $clean = [];

        foreach ($files as $index => $path) {
            if (explode('.', $path)[1] === 'DS_Store') {
                unset($files[$index]);
            } else {
                $clean[] = $path;
            }
        }

        return $clean;
    }
}
