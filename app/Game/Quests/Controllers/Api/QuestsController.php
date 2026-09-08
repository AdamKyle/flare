<?php

namespace App\Game\Quests\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Events\Values\EventType;
use App\Game\Quests\Requests\QuestTreeRequest;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Quests\Services\CharacterQuestAvailabilityService;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Quests\Services\QuestReadService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuestsController extends Controller
{
    public function __construct(
        private readonly QuestHandlerService $questHandlerService,
        private readonly BuildQuestCacheService $buildQuestCacheService,
        private readonly AutomationRestrictionService $automationRestrictionService,
        private readonly QuestReadService $questReadService,
        private readonly CharacterQuestAvailabilityService $characterQuestAvailabilityService,
    ) {}

    /**
     * Return the available Quests for the character.
     */
    public function index(Character $character): JsonResponse
    {
        return response()->json([
            'completed_quests' => $character->questsCompleted()->whereNotNull('quest_id')->pluck('quest_id'),
            'quests' => $this->buildQuestCacheService->getRegularQuests(),
            'raid_quests' => $this->buildQuestCacheService->fetchActiveRaidQuests(),
            'player_plane' => $character->map->gameMap->name,
            'is_winter_event' => Event::where('type', EventType::WINTER_EVENT)->count() > 0,
            'is_delusional_memories' => Event::where('type', EventType::DELUSIONAL_MEMORIES_EVENT)->count() > 0,
        ]);
    }

    /**
     * Return the requested Quest details for the character.
     */
    public function quest(Quest $quest, Character $character): JsonResponse
    {
        $quest = $quest->loadRelations();

        if ($quest->unlocks_skill) {
            $quest->unlocks_skill_name = SkillTypeValue::tryFrom($quest->unlocks_skill_type)->getNamedValue();
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

    /**
     * Return the factual Quest browse options for the character.
     */
    public function browseOptions(Character $character): JsonResponse
    {
        return response()->json($this->characterQuestAvailabilityService->browseOptions($this->questReadService->browseOptions()));
    }

    /**
     * Return the factual Quest tree with Character completion state, filtered to currently available Quests.
     */
    public function tree(QuestTreeRequest $request, Character $character): JsonResponse
    {
        $tree = $this->characterQuestAvailabilityService->tree($this->questReadService->tree($request->mapId(), $request->kind()));

        return response()->json([
            'quests' => $tree,
            'completed_quest_ids' => $this->completedQuestIds($character),
        ]);
    }

    /**
     * Return factual Quest detail with Character completion and hand-in readiness.
     */
    public function detail(Character $character, Quest $quest): JsonResponse
    {
        if (! $this->characterQuestAvailabilityService->isQuestAvailable($quest)) {
            return response()->json(['message' => 'That Quest is not currently available.'], 404);
        }

        $completedQuestIds = $this->completedQuestIds($character);

        return response()->json([
            'quest' => $this->questReadService->detail($quest),
            'completed_quest_ids' => $completedQuestIds,
            'readiness' => $this->handInReadiness($character, $quest, $completedQuestIds),
            'quest_item_ownership' => $this->questItemOwnership($character, $quest, in_array($quest->id, $completedQuestIds, true)),
        ]);
    }

    /**
     * Hand in the requested Quest for the character.
     */
    public function handInQuest(Quest $quest, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::REGULAR_QUESTS);

        if (! is_null($restriction)) {
            return response()->json([
                'message' => $restriction['message'],
            ], 422);
        }

        if ($this->questHandlerService->shouldBailOnQuest($character, $quest)) {
            return response()->json([
                'message' => $this->questHandlerService->getBailMessage(),
            ], 422);
        }

        $characterIsAtLocation = $character->map()
            ->where('character_position_x', $quest->npc->x_position)
            ->where('character_position_y', $quest->npc->y_position)
            ->where('game_map_id', $quest->npc->game_map_id)
            ->exists();

        if (! $characterIsAtLocation) {
            $response = $this->questHandlerService->moveCharacter($character, $quest->npc);

            if ($response instanceof Character) {
                $response = $this->questHandlerService->handInQuest($character, $quest);
            }
        } else {
            $response = $this->questHandlerService->handInQuest($character, $quest);
        }

        if ($response['status'] === 422) {
            unset($response['status']);

            return response()->json($response, 422);
        }

        unset($response['status']);

        $response['message'] = 'You completed the quest: '.$quest->name.'. Above is the updated story for the quest.';

        return response()->json($response);
    }

    /**
     * Return the Character's completed Quest ids.
     */
    private function completedQuestIds(Character $character): array
    {
        return $character->questsCompleted()->whereNotNull('quest_id')->pluck('quest_id')->values()->all();
    }

    /**
     * Resolve truthful Character-adapter Quest Item ownership state for this selected Quest's Items.
     */
    private function questItemOwnership(Character $character, Quest $quest, bool $isCompleted): array
    {
        $ownership = [];

        foreach ($this->questItemIdsForOwnership($quest) as $itemId) {
            $state = $this->itemOwnershipState($character, $itemId, $isCompleted);

            if (! is_null($state)) {
                $ownership[$itemId] = $state;
            }
        }

        return $ownership;
    }

    /**
     * Resolve the Quest Item ids relevant to Character ownership for this selected Quest.
     */
    private function questItemIdsForOwnership(Quest $quest): array
    {
        $itemIds = array_filter([$quest->item_id, $quest->secondary_required_item]);

        if (! is_null($quest->reward_item) && $quest->rewardItem?->type === 'quest') {
            $itemIds[] = $quest->reward_item;
        }

        return array_unique($itemIds);
    }

    /**
     * Resolve a single Item's truthful `has`/`had` ownership state, or null when neither is provable.
     */
    private function itemOwnershipState(Character $character, int $itemId, bool $isCompleted): ?string
    {
        if ($character->inventory->slots()->where('item_id', $itemId)->exists()) {
            return 'has';
        }

        if ($isCompleted) {
            return 'had';
        }

        return null;
    }

    /**
     * Resolve the authoritative selected-Quest hand-in readiness.
     */
    private function handInReadiness(Character $character, Quest $quest, array $completedQuestIds): array
    {
        if (in_array($quest->id, $completedQuestIds, true)) {
            return [
                'can_hand_in' => false,
                'message' => null,
            ];
        }

        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::REGULAR_QUESTS);

        if (! is_null($restriction)) {
            return [
                'can_hand_in' => false,
                'message' => $restriction['message'],
            ];
        }

        if ($this->questHandlerService->shouldBailOnQuest($character, $quest)) {
            return [
                'can_hand_in' => false,
                'message' => $this->questHandlerService->getBailMessage(),
            ];
        }

        return [
            'can_hand_in' => true,
            'message' => null,
        ];
    }
}
