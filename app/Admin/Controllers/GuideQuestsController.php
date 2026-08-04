<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\GuideQuests\GuideQuestsExport;
use App\Admin\Import\GuideQuests\GuideQuests;
use App\Admin\Requests\GuideQuestManagement;
use App\Admin\Requests\GuideQuestsImport;
use App\Admin\Services\GuideQuestService;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapName;
use App\Game\Skills\Values\SkillTypeValue;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class GuideQuestsController extends Controller
{
    private const BATCH_CRAFTING_TYPES = [
        'craft' => 'Craft For Experience',
        'craft_and_enchant' => 'Craft and Enchant For Experience',
        'alchemy' => 'Alchemy For Experience',
        'trinketry' => 'Trinketry For Experience',
    ];

    private GuideQuestService $guideQuestService;

    public function __construct(GuideQuestService $guideQuestService)
    {
        $this->guideQuestService = $guideQuestService;
    }

    public function index()
    {
        return view('admin.guide-quests.index');
    }

    public function export()
    {
        $response = Excel::download(new GuideQuestsExport, 'guide_quests.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    public function import(GuideQuestsImport $request)
    {
        Excel::import(new GuideQuests, $request->guide_quests_import);

        return redirect()->back()->with('success', 'imported guide quest data.');
    }

    public function exportGuideQuests()
    {
        return view('admin.guide-quests.export');
    }

    public function importGuideQuests()
    {
        return view('admin.guide-quests.import');
    }

    public function show(GuideQuest $guideQuest)
    {
        return view('admin.guide-quests.show', [
            'guideQuest' => $guideQuest,
        ]);
    }

    public function create()
    {
        return view('admin.guide-quests.manage', [
            'guideQuest' => null,
            'gameSkills' => GameSkill::pluck('name', 'id')->toArray(),
            'factionMaps' => GameMap::whereNotIn('name', [
                MapName::PURGATORY->value,
                MapName::ICE_PLANE->value,
            ])->pluck('name', 'id')->toArray(),
            'quests' => Quest::pluck('name', 'id')->toArray(),
            'questItems' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives' => PassiveSkill::pluck('name', 'id')->toArray(),
            'skillTypes' => SkillTypeValue::getValues(),
            'kingdomBuildings' => GameBuilding::pluck('name', 'id')->toArray(),
            'events' => EventType::getOptionsForSelect(),
            'guideQuests' => GuideQuest::pluck('name', 'id')->toArray(),
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'itemSpecialtyTypes' => ItemSpecialtyType::getValuesForSelect(),
            'batchCraftingTypes' => self::BATCH_CRAFTING_TYPES,
            'batchCraftedItemOptions' => $this->batchCraftedItemOptions(),
        ]);
    }

    public function store(GuideQuestManagement $request)
    {
        $params = $this->guideQuestService->cleanRequest($request->all());

        $params['instructions'] = str_replace('<p><br></p>', '', $params['instructions']);
        $params['desktop_instructions'] = str_replace('<p><br></p>', '', $params['desktop_instructions']);
        $params['mobile_instructions'] = str_replace('<p><br></p>', '', $params['mobile_instructions']);

        $guideQuest = GuideQuest::updateOrCreate(['id' => $params['id']], $params);

        return response()->redirectToRoute('admin.guide-quests.show', ['guideQuest' => $guideQuest->id])->with('success', 'Saved Guide Quest');
    }

    public function edit(GuideQuest $guideQuest)
    {
        return view('admin.guide-quests.manage', [
            'guideQuest' => $guideQuest,
            'gameSkills' => GameSkill::pluck('name', 'id')->toArray(),
            'factionMaps' => GameMap::whereNotIn('name', [
                MapName::PURGATORY->value,
                MapName::ICE_PLANE->value,
            ])->pluck('name', 'id')->toArray(),
            'quests' => Quest::pluck('name', 'id')->toArray(),
            'questItems' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives' => PassiveSkill::pluck('name', 'id')->toArray(),
            'skillTypes' => SkillTypeValue::getValues(),
            'kingdomBuildings' => GameBuilding::pluck('name', 'id')->toArray(),
            'events' => EventType::getOptionsForSelect(),
            'guideQuests' => GuideQuest::pluck('name', 'id')->toArray(),
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'itemSpecialtyTypes' => ItemSpecialtyType::getValuesForSelect(),
            'batchCraftingTypes' => self::BATCH_CRAFTING_TYPES,
            'batchCraftedItemOptions' => $this->batchCraftedItemOptions(),
        ]);
    }

    public function delete(GuideQuest $guideQuest)
    {
        $guideQuest->delete();

        QuestsCompleted::where('guide_quest_id', $guideQuest->id)->delete();

        return response()->redirectToRoute('admin.guide-quests')->with('success', 'Deleted guide quest.');
    }

    private function batchCraftedItemOptions(): array
    {
        $validTypes = array_merge(
            ItemType::validWeapons(),
            ArmourType::allTypes(),
            [
                ItemType::RING->value,
                ItemType::SPELL_DAMAGE->value,
                ItemType::SPELL_HEALING->value,
            ],
        );

        $craftedItems = Item::where('can_craft', true)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereIn('type', $validTypes)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Item $item) => [$item->id => $this->batchCraftedItemOptionLabel($item)]);

        $alchemyItems = Item::where('type', 'alchemy')
            ->orderBy('alchemy_type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Item $item) => [$item->id => $this->batchCraftedItemOptionLabel($item)]);

        return $craftedItems->union($alchemyItems)->toArray();
    }

    private function batchCraftedItemOptionLabel(Item $item): string
    {
        if ($item->type === 'alchemy') {
            return 'Alchemy > '.$this->alchemyItemTypeName($item->alchemy_type).' > '.$item->name;
        }

        if (in_array($item->type, ItemType::validWeapons(), true)) {
            return 'Crafted > Weapon > '.$this->batchCraftedItemTypeName($item->type).' > '.$item->name;
        }

        if (in_array($item->type, ArmourType::allTypes(), true)) {
            return 'Crafted > Armour > '.$this->batchCraftedItemTypeName($item->type).' > '.$item->name;
        }

        if ($item->type === ItemType::RING->value) {
            return 'Crafted > Ring > Ring > '.$item->name;
        }

        return 'Crafted > Spell > '.$this->batchCraftedItemTypeName($item->type).' > '.$item->name;
    }

    private function batchCraftedItemTypeName(string $type): string
    {
        return match ($type) {
            ItemType::DAGGER->value => 'Daggers',
            ItemType::SPELL_DAMAGE->value => 'Spell Damage',
            ItemType::SPELL_HEALING->value => 'Spell Healing',
            default => ItemType::getProperNameForType($type),
        };
    }

    private function alchemyItemTypeName(?string $alchemyType): string
    {
        return match ($alchemyType) {
            AlchemyItemType::INCREASE_STATS->value => 'Increases Stats',
            AlchemyItemType::INCREASE_SKILL_TYPE->value => 'Increases Training Skills',
            AlchemyItemType::INCREASE_DAMAGE->value => 'Increases Damage',
            AlchemyItemType::INCREASE_ARMOUR->value => 'Increases Armour',
            AlchemyItemType::INCREASE_HEALING->value => 'Increases Healing',
            AlchemyItemType::INCREASE_ALCHEMY_SKILL->value => 'Increases Alchemy Skill',
            AlchemyItemType::DAMAGES_KINGDOMS->value => 'Damages Kingdoms',
            AlchemyItemType::HOLY_OILS->value => 'Holy Oils',
            default => ItemType::getProperNameForType($alchemyType ?? 'alchemy'),
        };
    }
}
