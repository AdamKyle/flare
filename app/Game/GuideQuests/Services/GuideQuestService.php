<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Item;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\GuideQuest;
use App\Flare\Values\AutomationType;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Messages\Events\ServerMessageEvent;

class GuideQuestService {

    use HandleCharacterLevelUp;

    public function fetchQuestForCharacter(Character $character): GuideQuest | null {
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

        return $quest;
    }

    public function handInQuest(Character $character, GuideQuest $quest) {
        if (!$this->canHandInQuest($character, $quest)) {
            return false;
        }

        $gold      = $character->gold + ($quest->reward_level * 1000);
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

        event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format(($quest->reward_level * 1000)) . ' Gold.'));

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
        $character->update([
            'xp' => $guideQuest->reward_xp
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        return $character;
    }

    public function canHandInQuest(Character $character, GuideQuest $quest): bool {
        $alreadyCompleted = $character->questsCompleted()->where('guide_quest_id', $quest->id)->first();

        if (!is_null($alreadyCompleted)) {
            return false;
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty()) {
            return false;
        }

        $attributes = [];

        if (!is_null($quest->required_level)) {
            if ($character->level >= $quest->required_level) {
                $attributes[] = 'required_level';
            }
        }

        if (!is_null($quest->required_skill)) {
            $requiredSkill = $character->skills()->where('game_skill_id', $quest->required_skill)->first();

            if ($requiredSkill->level >= $quest->required_skill_level) {
                $attributes[] = 'required_skill_level';
            }
        }

        if (!is_null($quest->required_faction_id)) {
            $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

            if ($faction->current_level >= $quest->required_faction_level) {
                $attributes[] = 'required_faction_level';
            }
        }

        if (!is_null($quest->required_game_map_id)) {
            $gameMap = GameMap::find($quest->required_game_map_id);

            $canHandIn = $character->inventory->slots->filter(function($slot) use($gameMap) {
                return $slot->item->type === 'quest' && $slot->item->id === $gameMap->map_required_item->id;
            })->isNotEmpty();

            if ($canHandIn) {
                $attributes[] = 'required_game_map_id';
            }
        }

        if (!is_null($quest->required_quest_id)) {
            $canHandIn = !is_null($character->questsCompleted()->where('quest_id', $quest->required_quest_id)->first());

            if ($canHandIn) {
                $attributes[] = 'required_quest_id';
            }
        }

        if (!is_null($quest->required_quest_item_id)) {
            $canHandIn = $character->inventory->slots->filter(function($slot) use($quest) {
                return $slot->item->type === 'quest' && $slot->item->id === $quest->required_quest_item_id;
            })->isNotEmpty();

            if ($canHandIn) {
                $attributes[] = 'required_quest_item_id';
            }
        }

        if (!is_null($quest->required_kingdoms)) {
            if ($character->kingdoms->count() >= $quest->required_kingdoms) {
                $attributes[] = 'required_kingdoms';
            }
        }

        if (!is_null($quest->required_kingdom_level)) {
            foreach ($character->kingdoms as $kingdom) {
                if ($kingdom->buildings->sum('level') >= $quest->required_kingdom_level) {
                    $attributes[] = 'required_kingdom_level';

                    break;
                }
            }
        }

        if (!is_null($quest->required_kingdom_units)) {
            foreach ($character->kingdoms as $kingdom) {
                if ($kingdom->units->sum('amount') >= $quest->required_kingdom_units) {
                    $attributes[] = 'required_kingdom_units';

                    break;
                }
            }
        }

        if (!is_null($quest->required_passive_skill) && !is_null($quest->required_passive_level)) {
            $requiredSkill = $character->passiveSkills()->where('passive_skill_id', $quest->required_passive_skill)->first();

            if ($requiredSkill->current_level >= $quest->required_passive_level) {
                $attributes[] = 'required_passive_level';
            }
        }

        if (!is_null($quest->required_shards)) {
            if ($character->shards >= $quest->required_shards) {
                $attributes[] = 'required_shards';
            }
        }

        if (!empty($attributes)) {
            $requiredAttributes = $this->requiredAttributeNames($quest);

            return $attributes === $requiredAttributes;
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

            if (str_contains($key, 'required') !== false) {
                if (!is_null($attributes[$key])) {
                    $requiredAttributes[] = $key;
                }
            }
        }

        return $requiredAttributes;
    }
}
