<?php

namespace App\Admin\Controllers\Api\GuideQuests;

use App\Admin\Requests\GuideQuestRequest;
use App\Admin\Requests\GuideQuestStoreRequest;
use App\Admin\Services\GuideQuestService;
use App\Admin\Transformers\GuideQuestTransformer;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class GuideQuestsController
{
    public function __construct(
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly Manager $manager,
        private readonly GuideQuestTransformer $guideQuestTransformer,
        private readonly GuideQuestService $guideQuestService,
    ) {}

    public function guideQuest(GuideQuestRequest $request): JsonResponse
    {

        $guideQuest = GuideQuest::find($request->guide_quest_id);
        $guideQuestData = null;

        if (! is_null($guideQuest)) {
            $guideQuestData = new Item($guideQuest, $this->guideQuestTransformer);
            $guideQuestData = $this->manager->setSerializer($this->plainDataSerializer)->createData($guideQuestData)->toArray();
        }

        return response()->json([
            'guide_quest' => $guideQuestData,
            'game_skills' => GameSkill::pluck('name', 'id')->toArray(),
            'faction_maps' => GameMap::whereNotIn('name', [
                MapNameValue::PURGATORY,
                MapNameValue::ICE_PLANE,
            ])->pluck('name', 'id')->toArray(),
            'quests' => Quest::pluck('name', 'id')->toArray(),
            'quest_items' => \App\Flare\Models\Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives' => PassiveSkill::pluck('name', 'id')->toArray(),
            'skill_types' => SkillTypeValue::getValues(),
            'kingdom_buildings' => GameBuilding::pluck('name', 'id')->toArray(),
            'events' => EventType::getOptionsForSelect(),
            'guide_quests' => GuideQuest::pluck('name', 'id')->toArray(),
            'game_maps' => GameMap::pluck('name', 'id')->toArray(),
            'item_specialty_types' => ItemSpecialtyType::getValuesForSelect(),
        ]);
    }

    public function storeFormResponse(GuideQuestStoreRequest $guideQuestStoreRequest): JsonResponse
    {

        $request = $guideQuestStoreRequest->all();

        $guideQuest = $this->guideQuestService->upsert($request, new GuideQuest());

        $guideQuestData = new Item($guideQuest, $this->guideQuestTransformer);
        $guideQuestData = $this->manager->setSerializer($this->plainDataSerializer)->createData($guideQuestData)->toArray();

        return response()->json([
            'guide_quest' => $guideQuestData,
        ]);
    }
}
