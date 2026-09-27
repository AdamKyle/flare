<?php

namespace App\Admin\Controllers\Api\GuideQuests;

use App\Admin\Requests\GuideQuestIndexRequest;
use App\Admin\Requests\GuideQuestRequest;
use App\Admin\Requests\GuideQuestStoreRequest;
use App\Admin\Services\GuideQuestAdminReadService;
use App\Admin\Services\GuideQuestService;
use App\Admin\Transformers\GuideQuestDetailTransformer;
use App\Admin\Transformers\GuideQuestListTransformer;
use App\Admin\Transformers\GuideQuestTransformer;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapName;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Http\JsonResponse;

class GuideQuestsController
{
    /**
     * @param Pagination $pagination
     * @param GuideQuestAdminReadService $guideQuestAdminReadService
     * @param GuideQuestListTransformer $guideQuestListTransformer
     * @param GuideQuestTransformer $guideQuestTransformer
     * @param GuideQuestDetailTransformer $guideQuestDetailTransformer
     * @param GuideQuestService $guideQuestService
     */
    public function __construct(
        private readonly Pagination $pagination,
        private readonly GuideQuestAdminReadService $guideQuestAdminReadService,
        private readonly GuideQuestListTransformer $guideQuestListTransformer,
        private readonly GuideQuestTransformer $guideQuestTransformer,
        private readonly GuideQuestDetailTransformer $guideQuestDetailTransformer,
        private readonly GuideQuestService $guideQuestService,
    ) {}

    /**
     * Return the paginated Admin Guide Quest list.
     *
     * @param GuideQuestIndexRequest $request
     * @return JsonResponse
     */
    public function index(GuideQuestIndexRequest $request): JsonResponse
    {
        return response()->json($this->pagination->transformLengthAwarePaginator(
            $this->guideQuestAdminReadService->paginate($request),
            $this->guideQuestListTransformer
        ));
    }

    /**
     * Return the full factual Admin Guide Quest detail.
     *
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function show(GuideQuest $guideQuest): JsonResponse
    {
        return response()->json($this->guideQuestDetailTransformer->transform($guideQuest));
    }

    /**
     * Return the Guide Quest editor data and form options.
     *
     * @param GuideQuestRequest $request
     * @return JsonResponse
     */
    public function guideQuest(GuideQuestRequest $request): JsonResponse
    {
        $guideQuest = GuideQuest::find($request->validated('guide_quest_id'));

        return response()->json([
            'guide_quest' => is_null($guideQuest) ? null : $this->guideQuestTransformer->transform($guideQuest),
            'game_skills' => GameSkill::pluck('name', 'id')->toArray(),
            'faction_maps' => GameMap::whereNotIn('name', [MapName::PURGATORY->value, MapName::ICE_PLANE->value])->pluck('name', 'id')->toArray(),
            'quests' => Quest::pluck('name', 'id')->toArray(),
            'quest_items' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives' => PassiveSkill::pluck('name', 'id')->toArray(),
            'skill_types' => SkillTypeValue::getValues(),
            'kingdom_buildings' => GameBuilding::pluck('name', 'id')->toArray(),
            'events' => EventType::getOptionsForSelect(),
            'guide_quests' => GuideQuest::pluck('name', 'id')->toArray(),
            'game_maps' => GameMap::pluck('name', 'id')->toArray(),
            'item_specialty_types' => ItemSpecialtyType::getValuesForSelect(),
        ]);
    }

    /**
     * Persist Guide Quest editor content and return the saved detail.
     *
     * @param GuideQuestStoreRequest $guideQuestStoreRequest
     * @return JsonResponse
     */
    public function storeFormResponse(GuideQuestStoreRequest $guideQuestStoreRequest): JsonResponse
    {
        $guideQuest = $this->guideQuestService->upsert($guideQuestStoreRequest->all(), new GuideQuest);

        return response()->json(['guide_quest' => $this->guideQuestTransformer->transform($guideQuest)]);
    }
}
