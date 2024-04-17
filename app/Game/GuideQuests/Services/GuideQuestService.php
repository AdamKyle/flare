<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GuideQuest;
use App\Flare\Values\AutomationType;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use App\Game\Messages\Events\ServerMessageEvent;

class GuideQuestService {

    use HandleCharacterLevelUp;

    private GuideQuestRequirementsService $guideQuestRequirementsService;

    private array $completedAttributes = [];

    public function __construct(GuideQuestRequirementsService $guideQuestRequirementsService) {
        $this->guideQuestRequirementsService = $guideQuestRequirementsService;
    }

    public function fetchQuestForCharacter(Character $character): array | null {

        $quest = $this->fetchNextGuideQuest($character);

        if (is_null($quest)) {
            return null;
        }

        $canHandIn = $this->canHandInQuest($character, $quest, true);

        return [
            'quest' => $quest,
            'completed_requirements' => $this->completedAttributes,
            'can_hand_in' => $canHandIn,
        ];
    }

    public function handInQuest(Character $character, GuideQuest $quest): bool {
        if (!$this->canHandInQuest($character, $quest)) {
            return false;
        }

        $gold      = $character->gold + $quest->gold_reward;
        $goldDust  = $character->gold_dust + $quest->gold_dust_reward;
        $shards    = $character->shards + $quest->shards_reward;

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        if ($goldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $goldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        if ($shards >= MaxCurrenciesValue::MAX_SHARDS) {
            $shards = MaxCurrenciesValue::MAX_SHARDS;
        }

        if ($quest->gold_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format($quest->gold_reward) . ' Gold.'));
        }

        if ($quest->gold_dust_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format($quest->gold_dust_reward) . ' Gold Dust.'));
        }

        if ($quest->shards_reward > 0) {
            event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format($quest->shards_reward) . ' Shards.'));
        }


        $character = $this->giveXP($character, $quest);

        $character->update([
            'gold'      => $gold,
            'gold_dust' => $goldDust,
            'shards'    => $shards,
        ]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ShowGuideQuestCompletedToast($character->user, false));

        return true;
    }

    public function giveXP(Character $character, GuideQuest $guideQuest): Character {

        if ($guideQuest->xp_reward <= 0) {
            return $character;
        }

        $character->update([
            'xp' => $character->xp + $guideQuest->xp_reward
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format($guideQuest->xp_reward) . ' XP.'));

        return $character;
    }

    public function canHandInQuest(Character $character, GuideQuest $quest, bool $ignoreAutomation = false): bool {

        $this->completedAttributes = [];

        $alreadyCompleted = $character->questsCompleted()->where('guide_quest_id', $quest->id)->first();
        $stats            = ['str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'];

        if (!is_null($alreadyCompleted)) {
            return false;
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty() && !$ignoreAutomation) {
            return false;
        }

        $this->completedAttributes = $this->guideQuestRequirementsService->requiredLevelCheck($character, $quest)
            ->requiredQuest($character, $quest)
            ->requiredSkillCheck($character, $quest)
            ->requiredSkillCheck($character, $quest, false)
            ->requiredSkillTypeCheck($character, $quest)
            ->requiredFactionLevel($character, $quest)
            ->requiredGameMapAccess($character, $quest)
            ->requiredQuestItem($character, $quest)
            ->requiredQuestItem($character, $quest, false)
            ->requiredKingdomCount($character, $quest)
            ->requiredKingdomBuildingLevel($character, $quest)
            ->requiredKingdomUnitCount($character, $quest)
            ->requiredKingdomPassiveLevel($character, $quest)
            ->requiredCurrency($character, $quest, 'gold')
            ->requiredCurrency($character, $quest, 'gold_dust')
            ->requiredCurrency($character, $quest, 'shards')
            ->requiredCurrency($character, $quest, 'copper_coins')
            ->requiredTotalStats($character, $quest, $stats)
            ->requiredStats($character, $quest, $stats)
            ->requiredClassRanksEquipped($character, $quest)
            ->requiredClassRankLevel($character, $quest)
            ->requiredKingdomGoldBarsAmount($character, $quest)
            ->requiredKingdomSpecificBuildingLevel($character, $quest)
            ->requiredGlobalEventKillAmount($character, $quest)
            ->requirePlayerToBeOnASpecificMap($character, $quest)
            ->requiredSpecialtyType($character, $quest)
            ->requiredHolyStacks($character, $quest)
            ->requiredFameLevel($character, $quest)
            ->getFinishedRequirements();

        if (!empty($this->completedAttributes)) {
            $requiredAttributes = $this->requiredAttributeNames($quest);

            $difference = array_diff($requiredAttributes, $this->completedAttributes);

            $this->guideQuestRequirementsService->resetFinishedRequirements();

            if (empty($difference)) {
                return true;
            }
        }

        return false;
    }

    protected function fetchNextGuideQuest(Character $character): GuideQuest | null {

        $winterEvent          = Event::where('type' , EventType::WINTER_EVENT)->first();
        $delusionalEvent      = Event::where('type' , EventType::DELUSIONAL_MEMORIES_EVENT)->first();
        $unlocksAtLevelQuest  = GuideQuest::where('unlock_at_level', '<=', $character->level)->whereNull('only_during_event')->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        $nextGuideQuest       = null;

        if (!is_null($winterEvent)) {
            $unlocksAtLevelQuest  = GuideQuest::where('unlock_at_level', '<=', $character->level)->where('only_during_event', EventType::WINTER_EVENT)->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        }

        if (!is_null($delusionalEvent)) {
            $unlocksAtLevelQuest  = GuideQuest::where('unlock_at_level', '<=', $character->level)->where('only_during_event', EventType::DELUSIONAL_MEMORIES_EVENT)->whereNull('parent_id')->orderBy('unlock_at_level', 'asc')->first();
        }

        if (!is_null($unlocksAtLevelQuest)) {
            $nextGuideQuest = $this->fetchNextEventQuest($character, $unlocksAtLevelQuest);
        }

        if (!is_null($winterEvent) && is_null($nextGuideQuest)) {
            $eventGuideQuest = GuideQuest::where('only_during_event', EventType::WINTER_EVENT)->whereNull('parent_id')->first();

            if (!is_null($eventGuideQuest)) {
                $nextGuideQuest = $this->fetchNextEventQuest($character, $eventGuideQuest);
            }
        }

        if (!is_null($delusionalEvent) && is_null($nextGuideQuest)) {
            $delusionalEventQuest = GuideQuest::where('only_during_event', EventType::DELUSIONAL_MEMORIES_EVENT)->whereNull('parent_id')->first();

            if (!is_null($delusionalEventQuest)) {
                $nextGuideQuest = $this->fetchNextEventQuest($character, $delusionalEventQuest);
            }
        }

        if (!is_null($nextGuideQuest)) {
            return $nextGuideQuest;
        }

        return $this->fetchNextRegularGuideQuest($character);
    }

    protected function fetchNextRegularGuideQuest(Character $character): GuideQuest | null {
        $lastCompletedGuideQuest = $character->questsCompleted()
            ->whereHas('guideQuest', function($query) {
                $query->whereNull('only_during_event')
                      ->whereNull('unlock_at_level');
            })
            ->orderByDesc('guide_quest_id')
            ->first();

        if (is_null($lastCompletedGuideQuest)) {
            return GuideQuest::first();
        }

        $questId = GuideQuest::whereNull('only_during_event')
            ->whereNull('unlock_at_level')
            ->where('id', '>', $lastCompletedGuideQuest->guide_quest_id)
            ->min('id');

        return GuideQuest::find($questId);
    }

    protected function fetchNextEventQuest(Character $character, GuideQuest $initialEventGuideQuest): GuideQuest | null {

        if (!is_null($initialEventGuideQuest->unlock_at_level)) {
            if ($character->level < $initialEventGuideQuest->unlock_at_level) {
                return null;
            }
        }

        $completedFirstEventQuest = $character->questsCompleted()
                ->where('guide_quest_id', $initialEventGuideQuest->id)
                ->first();

        if (is_null($completedFirstEventQuest)) {
            return $initialEventGuideQuest;
        }

        $completedIds = $character->questsCompleted()->whereNotNull('guide_quest_id')->pluck('guide_quest_id')->toArray();

        if (!in_array($initialEventGuideQuest->id, $completedIds)) {
            return GuideQuest::where('parent_id', $initialEventGuideQuest->id)->orderBy('id')->first();
        }

        $nextGuideQuest = GuideQuest::where('parent_id', $initialEventGuideQuest->id)->orderBy('id')->first();

        if (is_null($nextGuideQuest)) {
            return null;
        }

        return $this->fetchNextEventQuest($character, $nextGuideQuest);
    }

    protected function requiredAttributeNames(GuideQuest $quest): array {

        $requiredAttributes = [];

        $attributes = $quest->getAttributes();

        foreach ($attributes as $key => $value) {
            if ($key === 'required_skill') {
                continue;
            }

            if ($key === 'required_passive_skill') {
                continue;
            }

            if ($key === 'required_faction_id') {
                continue;
            }

            if ($key === 'required_secondary_skill') {
                continue;
            }

            if ($key === 'required_skill_type') {
                continue;
            }

            if ($key === 'required_skill_type') {
                continue;
            }

            if ($key === 'required_kingdom_building_id') {
                continue;
            }

            if (str_contains($key, 'required') !== false) {
                if (!is_null($attributes[$key])) {
                    $requiredAttributes[] = $key;
                }
            }
        }

        return $requiredAttributes;
    }
}
