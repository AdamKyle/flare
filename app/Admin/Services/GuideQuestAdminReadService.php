<?php

namespace App\Admin\Services;

use App\Admin\Requests\GuideQuestIndexRequest;
use App\Admin\Transformers\GuideQuestTransformer;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughEvent;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughTarget;
use App\Game\Maps\Values\MapName;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GuideQuestAdminReadService
{
    /**
     * @param GuideQuestTransformer $guideQuestTransformer
     */
    public function __construct(private readonly GuideQuestTransformer $guideQuestTransformer) {}

    /**
     * Paginate Guide Quests for the validated Admin list request.
     *
     * @param GuideQuestIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(GuideQuestIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return GuideQuest::query()
            ->when(! empty($searchText), function (Builder $query) use ($searchText): void {
                $query->where(function (Builder $searchQuery) use ($searchText): void {
                    $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                        ->orWhere('intro_text', 'LIKE', '%'.$searchText.'%');
                });
            })
            ->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Return the Guide Quest editor data and factual option lists.
     *
     * @param int $guideQuestId
     * @return array
     */
    public function editorData(int $guideQuestId): array
    {
        $guideQuest = GuideQuest::find($guideQuestId);

        return [
            'guide_quest' => is_null($guideQuest) ? null : $this->guideQuestTransformer->transform($guideQuest),
            'game_skills' => GameSkill::pluck('name', 'id')->toArray(),
            'faction_maps' => GameMap::whereIn('name', MapName::factionMapNames())->pluck('name', 'id')->toArray(),
            'quests' => Quest::pluck('name', 'id')->toArray(),
            'quest_items' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives' => PassiveSkill::pluck('name', 'id')->toArray(),
            'skill_types' => SkillTypeValue::getValues(),
            'kingdom_buildings' => GameBuilding::pluck('name', 'id')->toArray(),
            'events' => EventType::getOptionsForSelect(),
            'guide_quests' => GuideQuest::pluck('name', 'id')->toArray(),
            'game_maps' => GameMap::pluck('name', 'id')->toArray(),
            'item_specialty_types' => ItemSpecialtyType::getValuesForSelect(),
            'walkthrough_steps' => is_null($guideQuest) ? [] : $guideQuest->walkthroughSteps()->get(),
            'walkthrough_target_options' => GuideQuestWalkthroughTarget::options(),
            'walkthrough_event_options' => GuideQuestWalkthroughEvent::options(),
            'walkthrough_step_type_options' => GuideQuestWalkthroughStepType::options(),
        ];
    }
}
