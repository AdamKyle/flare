<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Values\AutomationType;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Messages\Events\ServerMessageEvent;

class GuideQuestService {

    use HandleCharacterLevelUp;

    private GuideQuestRequirementsService $guideQuestRequirementsService;

    private array $completedAttributes = [];

    public function __construct(GuideQuestRequirementsService $guideQuestRequirementsService) {
        $this->guideQuestRequirementsService = $guideQuestRequirementsService;
    }

    public function fetchQuestForCharacter(Character $character): array | null {

        $lastCompletedGuideQuest = $character->questsCompleted()
            ->whereNotNull('guide_quest_id')
            ->orderByDesc('guide_quest_id')
            ->first();

        if (is_null($lastCompletedGuideQuest)) {
            $quest = GuideQuest::first();
        } else {
            $questId = GuideQuest::where('id', '>', $lastCompletedGuideQuest->guide_quest_id)->min('id');
            $quest   = GuideQuest::find($questId);
        }

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

        event(new UpdateTopBarEvent($character->refresh()));

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
            ->requiredMercenaryCheck($character, $quest)
            ->requiredMercenaryCheck($character, $quest, false)
            ->requiredKingdomCount($character, $quest)
            ->requiredKingdomBuildingLevel($character, $quest)
            ->requiredKingdomUnitCount($character, $quest)
            ->requiredKingdomPassiveLevel($character, $quest)
            ->requiredCurrency($character, $quest, 'gold')
            ->requiredCurrency($character, $quest, 'gold_dust')
            ->requiredCurrency($character, $quest, 'shards')
            ->requiredTotalStats($character, $quest, $stats)
            ->requiredStats($character, $quest, $stats)
            ->requiredClassRanksEquipped($character, $quest)
            ->requiredClassRankLevel($character, $quest)
            ->requiredKingdomGoldBarsAmount($character, $quest)
            ->requiredKingdomSpecificBuildingLevel($character, $quest)
            ->getFinishedRequirements();

        if (!empty($this->completedAttributes)) {
            $requiredAttributes = $this->requiredAttributeNames($quest);

            $difference = array_diff($requiredAttributes, $this->completedAttributes);

            $this->guideQuestRequirementsService->resetFinishedRequirements();
            dump($difference);
            if (empty($difference)) {
                return true;
            }
        }

        return false;
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
