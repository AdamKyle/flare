<?php

namespace App\Http\Controllers;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GameUnit;
use App\Flare\Models\InfoPage;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Flare\Tables\TableQueryBuilder;
use App\Game\Core\Items\Services\ItemShowInformationService;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Monsters\Services\MonsterShowInformationService;
use App\Game\Core\Values\View\ClassBonusInformation;
use App\Game\Maps\Values\LocationType;
use App\Info\Tables\Definitions\LocationGemsTableDefinition;
use App\Info\Tables\Definitions\MapGemsTableDefinition;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Storage;

class InfoPageController extends Controller
{
    public function __construct(
        private readonly ClassBonusInformation $classBonusInformation,
        private readonly ItemShowInformationService $itemShowInformationService,
        private readonly MonsterShowInformationService $monsterShowInformationService,
    ) {}

    public function search(Request $request)
    {
        if (is_null($request->info_search)) {
            return response()->redirectToRoute('info.page', ['pageName' => 'home']);
        }

        $query = $request->info_search;
        $allPages = InfoPage::all();

        $searchResults = $allPages->filter(function ($page) use ($query) {
            foreach ($page->page_sections as $section) {
                if (stripos($section['content'] ?? '', $query) !== false) {
                    return true;
                }
            }

            return false;
        });

        return view('information.search-results', [
            'results' => $searchResults,
            'query' => $query,
        ]);
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function viewPage(Request $request, string $pageName)
    {
        $page = InfoPage::where('page_name', $pageName)->first();

        if (is_null($page)) {
            abort(404);
        }

        $sections = $page->page_sections;

        array_multisort(array_column($sections, 'order'), SORT_ASC, $sections);

        return view('information.core', [
            'pageTitle' => ucfirst(str_replace('-', ' ', $page->page_name)),
            'sections' => $sections,
            'pageId' => $page->id,
        ]);
    }

    public function viewRace(Request $request, GameRace $race)
    {
        return view('information.races.race', [
            'race' => $race,
        ]);
    }

    public function viewClass(Request $request, GameClass $class)
    {
        return view('information.classes.class', [
            'class' => $class,
            'classBonus' => $this->classBonusInformation->buildClassBonusDetailsForInfo($class->name),
        ]);
    }

    public function viewMap(GameMap $map)
    {

        $effects = match ($map->name) {
            'Labyrinth' => ItemEffectType::LABYRINTH->value,
            'Dungeons' => ItemEffectType::DUNGEON->value,
            'Shadow Plane' => ItemEffectType::SHADOW_PLANE->value,
            'Hell' => ItemEffectType::HELL->value,
            'Purgatory' => ItemEffectType::PURGATORY->value,
            default => '',
        };

        $walkOnWater = match ($map->name) {
            'Labyrinth', 'Surface' => ItemEffectType::WALK_ON_WATER->value,
            'Dungeons' => ItemEffectType::WALK_ON_DEATH_WATER->value,
            'Hell' => ItemEffectType::WALK_ON_MAGMA->value,
            'The Ice Plane' => ItemEffectType::WALK_ON_ICE->value,
            default => '',
        };

        return view('information.maps.map', [
            'map' => $map,
            'itemNeeded' => Item::where('effect', $effects)->first(),
            'walkOnWater' => Item::where('effect', $walkOnWater)->first(),
            'mapUrl' => Storage::disk('maps')->url($map->path),
        ]);
    }

    public function viewMapGems(Request $request): View
    {
        $columns = MapGemsTableDefinition::columns();

        return view('information.map-gems.index', [
            'paginator' => TableQueryBuilder::paginate(MapGemsTableDefinition::builder(), $columns, $request),
            'columns' => $columns,
        ]);
    }

    public function viewLocationGems(Request $request): View
    {
        $columns = LocationGemsTableDefinition::columns();

        return view('information.location-gems.index', [
            'paginator' => TableQueryBuilder::paginate(LocationGemsTableDefinition::builder(), $columns, $request),
            'columns' => $columns,
        ]);
    }

    public function viewMapGem(GameMapGemParamter $gameMapGemParamter): View
    {
        return view('information.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter->load('gameMap'),
        ]);
    }

    public function viewLocationGem(GameLocationGemParamter $gameLocationGemParamter): View
    {
        return view('information.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter->load('location.map'),
        ]);
    }

    public function viewSkill(Request $request, GameSkill $skill)
    {
        return view('information.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function viewClassSpecialty(Request $request, GameClassSpecial $gameClassSpecial)
    {
        return view('information.class-specialties.specialty', [
            'classSpecial' => $gameClassSpecial,
        ]);
    }

    public function viewMonster(Request $request, Monster $monster)
    {
        return view('information.monsters.monster', $this->monsterShowInformationService->details($monster));
    }

    public function viewLocation(Request $request, Location $location)
    {
        $locationType = null;

        $usedInQuest = null;

        if (! is_null($location->questRewardItem)) {
            $questItemId = $location->quest_reward_item_id;

            $usedInQuest = Quest::where(function ($query) use ($questItemId) {
                $query->where('item_id', $questItemId)
                    ->orWhere('secondary_required_item', $questItemId);
            })
                ->orderByRaw('CASE WHEN item_id = ? THEN 0 ELSE 1 END', [$questItemId])
                ->first();
        }

        if (! is_null($location->type)) {
            $locationType = LocationType::tryFrom($location->type);
        }

        return view('information.locations.location', [
            'location' => $location,
            'locationType' => $locationType,
            'usedInQuest' => $usedInQuest,
        ]);
    }

    public function viewUnit(Request $request, GameUnit $unit)
    {
        $belongsToKingdomBuilding = GameBuildingUnit::where('game_unit_id', $unit->id)->first();

        if (! is_null($belongsToKingdomBuilding)) {
            $belongsToKingdomBuilding = $belongsToKingdomBuilding->gameBuilding;
        }

        return view('information.units.unit', [
            'unit' => $unit,
            'building' => $belongsToKingdomBuilding,
            'requiredLevel' => GameBuildingUnit::where('game_building_id', $belongsToKingdomBuilding->id)
                ->where('game_unit_id', $unit->id)
                ->first()->required_level,
        ]);
    }

    public function viewBuilding(GameBuilding $building)
    {
        return view('information.buildings.building', [
            'building' => $building,
        ]);
    }

    public function viewItem(Request $request, Item $item)
    {
        return view('information.items.item', $this->itemShowInformationService->details($item));
    }

    public function viewAffix(Request $request, ItemAffix $affix)
    {
        return view('information.affixes.affix', [
            'itemAffix' => $affix,
        ]);
    }

    public function viewNpc(Npc $npc)
    {
        return view('information.npcs.npc', [
            'npc' => $npc,
        ]);
    }

    public function viewRaid(Raid $raid)
    {
        $monsters = Monster::whereIn('id', $raid->raid_monster_ids)->select('id', 'name')->get()->toArray();

        return view('information.raids.raid', [
            'raid' => $raid,
            'raidMonsters' => array_chunk($monsters, ceil(count($monsters) / 2)),
        ]);
    }

    /**
     * Show the public, read-only Quest detail page for the given Quest.
     *
     * @param Quest $quest Quest to view.
     * @return View Quest detail view.
     */
    public function viewQuest(Quest $quest): View
    {
        return view('information.quests.quest', [
            'quest' => $quest,
        ]);
    }

    /**
     * Show the public, read-only factual Quest tree page.
     *
     * @return View Quest tree view.
     */
    public function viewQuestTree(): View
    {
        return view('information.quests.quests');
    }

    public function viewPassiveSkill(PassiveSkill $passiveSkill)
    {
        return view('information.passive-skills.skill', [
            'skill' => $passiveSkill,
        ]);
    }

    public function itemSkill(ItemSkill $itemSkill)
    {
        return view('information.item-skills.skill', [
            'itemSkill' => $itemSkill,
        ]);
    }
}
