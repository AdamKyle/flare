<?php

namespace App\Http\Controllers;

use Storage;
use App\Flare\Models\Npc;
use App\Flare\Models\Item;
use App\Flare\Models\Raid;
use App\Flare\Models\Quest;
use Illuminate\Http\Request;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameUnit;
use App\Flare\Models\InfoPage;
use App\Flare\Models\Location;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\PassiveSkill;
use App\Flare\Values\LocationType;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationEffectValue;
use App\Game\Core\Values\View\ClassBonusInformation;
use App\Flare\Traits\Controllers\ItemsShowInformation;
use App\Flare\Traits\Controllers\MonstersShowInformation;

class InfoPageController extends Controller {

    use ItemsShowInformation, MonstersShowInformation;

    public function search(Request $request) {

        if (is_null($request->info_search)) {
            return response()->redirectToRoute('info.page', ['pageName' => 'home']);
        }

        $searchResults = InfoPage::whereRaw("JSON_EXTRACT(page_sections, '$[*].content') LIKE '%" . $request->info_search . "%'")->get();

        return view('information.search-results', [
            'results' => $searchResults,
            'query'   => $request->info_search,
        ]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function viewPage(Request $request, string $pageName) {
        $page = InfoPage::where('page_name', $pageName)->first();

        if (is_null($page)) {
            abort(404);
        }

        $sections = $page->page_sections;

        array_multisort(array_column($sections, 'order'), SORT_ASC, $sections);

        return view('information.core', [
            'pageTitle' => ucfirst(str_replace('-', ' ', $page->page_name)),
            'sections'  => $sections,
            'pageId'    => $page->id,
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
            'classBonus' => (new ClassBonusInformation())->buildClassBonusDetailsForInfo($class->name),
        ]);
    }

    public function viewMap(GameMap $map) {

        $effects = match ($map->name) {
            'Labyrinth'    => ItemEffectsValue::LABYRINTH,
            'Dungeons'     => ItemEffectsValue::DUNGEON,
            'Shadow Plane' => ItemEffectsValue::SHADOWPLANE,
            'Hell'         => ItemEffectsValue::HELL,
            'Purgatory'    => ItemEffectsValue::PURGATORY,
            default        => '',
        };

        $walkOnWater = match ($map->name) {
            'Labyrinth', 'Surface' => ItemEffectsValue::WALK_ON_WATER,
            'Dungeons'     => ItemEffectsValue::WALK_ON_DEATH_WATER,
            'Hell'         => ItemEffectsValue::WALK_ON_MAGMA,
            default        => '',
        };

        return view('information.maps.map', [
            'map'         => $map,
            'itemNeeded'  => Item::where('effect', $effects)->first(),
            'walkOnWater' => Item::where('effect', $walkOnWater)->first(),
            'mapUrl'      => Storage::disk('maps')->url($map->path),
        ]);
    }

    public function viewSkill(Request $request, GameSkill $skill) {
        return view('information.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function viewClassSpecialty(Request $request, GameClassSpecial $gameClassSpecial) {
        return view('information.class-specialties.specialty', [
            'classSpecial' => $gameClassSpecial,
        ]);
    }

    public function viewMonster(Request $request, Monster $monster) {
        return $this->renderMonsterShow($monster, 'information.monsters.monster');
    }

    public function viewLocation(Request $request, Location $location) {
        $increasesEnemyStrengthBy = null;
        $increasesDropChanceBy    = 0.0;
        $locationType             = null;

        if (!is_null($location->enemy_strength_type)) {
            $increasesEnemyStrengthBy = LocationEffectValue::getIncreaseName($location->enemy_strength_type);
            $increasesDropChanceBy    = (new LocationEffectValue($location->enemy_strength_type))->fetchDropRate();
        }

        $questItemDetails = [];

        if (!is_null($location->questRewardItem)) {
            $questItemDetails = $this->itemShowDetails($location->questRewardItem);
        }

        if (!is_null($location->type)) {
            $locationType = (new LocationType($location->type));
        }

        return view('information.locations.location', array_merge([
            'location'                 => $location,
            'increasesEnemyStrengthBy' => $increasesEnemyStrengthBy,
            'increasesDropChanceBy'    => $increasesDropChanceBy,
            'locationType'             => $locationType,
        ], $questItemDetails));
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

    public function viewBuilding(GameBuilding $building) {
        return view('information.buildings.building', [
            'building' => $building
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

    public function viewRaid(Raid $raid) {
        $monsters = Monster::whereIn('id', $raid->raid_monster_ids)->select('id', 'name')->get()->toArray();

        return view('information.raids.raid', [
            'raid'         => $raid,
            'raidMonsters' => array_chunk($monsters, ceil(count($monsters) / 2))
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

    public function viewPassiveSkill(PassiveSkill $passiveSkill) {
        return view('information.passive-skills.skill', [
            'skill' => $passiveSkill,
        ]);
    }

    public function itemSkill(ItemSkill $itemSkill) {
        return view('information.item-skills.skill', [
            'itemSkill' => $itemSkill,
        ]);
    }
}
