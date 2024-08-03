<?php

namespace App\Game\Quests\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Events\Values\EventType;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Http\Controllers\Controller;

class QuestsController extends Controller
{
    private QuestHandlerService $questHandler;

    private BuildQuestCacheService $buildQuestCacheService;

    public function __construct(QuestHandlerService $questHandlerService, BuildQuestCacheService $buildQuestCacheService)
    {
        $this->questHandler = $questHandlerService;
        $this->buildQuestCacheService = $buildQuestCacheService;
    }

    public function index(Character $character)
    {

        $eventWithRaid = Event::whereNotNull('raid_id')->first();

        return response()->json([
            'completed_quests' => $character->questsCompleted()->whereNotNull('quest_id')->pluck('quest_id'),
            'quests' => $this->buildQuestCacheService->getRegularQuests(),
            'raid_quests' => $this->buildQuestCacheService->fetchQuestsForRaid($eventWithRaid),
            'player_plane' => $character->map->gameMap->name,
            'is_winter_event' => Event::where('type', EventType::WINTER_EVENT)->count() > 0,
            'is_delusional_memories' => Event::where('type', EventType::DELUSIONAL_MEMORIES_EVENT)->count() > 0,
        ]);
    }

    public function quest(Quest $quest, Character $character)
    {
        $quest = $quest->loadRelations();

        if ($quest->unlocks_skill) {
            $quest->unlocks_skill_name = (new SkillTypeValue($quest->unlocks_skill_type))->getNamedValue();
        }

        if (! $quest->unlocks_skill) {
            $quest->unlocks_skill_name = 'N/A';
        }

        if (! is_null($quest->unlocks_feature)) {
            $quest->feature_to_unlock_name = $quest->unlocksFeature()->getNameOfFeature();
        } else {
            $quest->feature_to_unlock_name = null;
        }

        if (! is_null($quest->unlocks_passive_id)) {
            $quest->unlocks_passive_name = PassiveSkill::find($quest->unlocks_passive_id)->name;
        } else {
            $quest->unlocks_passive_name = null;
        }

        return response()->json($quest);
    }

    public function handInQuest(Quest $quest, Character $character)
    {
        if ($this->questHandler->shouldBailOnQuest($character, $quest)) {
            return response()->json([
                'message' => $this->questHandler->getBailMessage(),
            ], 422);
        }

        $characterIsAtLocation = $character->map()
            ->where('x_position', $quest->npc->x_position)
            ->where('y_position', $quest->npc->y_position)
            ->where('game_map_id', $quest->npc->game_map_id);

        if (! is_null($characterIsAtLocation)) {

            $response = $this->questHandler->moveCharacter($character, $quest->npc);

            if ($response instanceof Character) {
                $response = $this->questHandler->handInQuest($character, $quest);
            }
        } else {
            $response = $this->questHandler->handInQuest($character, $quest);
        }

        if ($response['status'] === 422) {
            unset($response['status']);

            return response()->json($response, 422);
        }

        unset($response['status']);

        $response['message'] = 'You completed the quest: '.$quest->name.'. Above is the updated story for the quest.';

        return response()->json($response);

    }
}
