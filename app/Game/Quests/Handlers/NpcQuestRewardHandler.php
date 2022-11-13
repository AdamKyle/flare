<?php

namespace App\Game\Quests\Handlers;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Quests\Events\UnlockSkillEvent;

class NpcQuestRewardHandler {

    use HandleCharacterLevelUp;

    private NpcServerMessageBuilder $npcServerMessageBuilder;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    public function processReward(Quest $quest, Npc $npc, Character $character): void {

        if ($this->questHasRewardItem($quest)) {
            $this->giveItem($character, $quest, $npc);
        }

        if ($this->questUnlocksSkill($quest)) {
            $this->unlockSkill($quest, $character, $npc);
        }

        if ($this->questRewardsGold($quest)) {
            $this->giveGold($character, $quest, $npc);
        }

        if ($this->questRewardsGoldDust($quest)) {
            $this->giveGoldDust($character, $quest, $npc);
        }

        if ($this->questRewardsShards($quest)) {
            $this->giveShards($character, $quest, $npc);
        }

        if ($this->questRewardsXP($quest)) {
            $this->giveXP($character, $quest);
        }

        if (!is_null($quest->unlocks_feature)) {
            if ($quest->unlocksFeature()->isMercenary()) {
                $character->update(['is_mercenary_unlocked' => true]);

                event(new UpdateCharacterStatus($character->refresh()));

                event(new ServerMessageEvent($character->user, 'You have unlocked a new game feature: Mercenaries!
                Go to your character sheet and click on the tab beside Factions to purchase mercenaries.
                You can ream more about them by clicking on Help I\'m Stuck! and selecting Mercenary under Game Systems.
                There is also a help link on the tab. mercenaries add new boons to those who farm currencies!'));
            }
        }

        $this->createQuestLog($character, $quest);
    }

    public function questHasRewardItem(Quest $quest): bool {
        return !is_null($quest->reward_item);
    }

    public function questUnlocksSkill(Quest $quest): bool {
        return $quest->unlocks_skill;
    }

    public function questRewardsGold(Quest $quest): bool {
        return !is_null($quest->reward_gold);
    }

    public function questRewardsGoldDust(Quest $quest): bool {
        return !is_null($quest->reward_gold_dust);
    }

    public function questRewardsShards(Quest $quest): bool {
        return !is_null($quest->reward_shards);
    }

    public function questRewardsXP(Quest $quest): bool {
        return !is_null($quest->reward_xp);
    }

    public function giveXP(Character $character, Quest $quest): void {
        $character->update([
            'xp' => $quest->reward_xp
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);
    }

    public function giveItem(Character $character, Quest $quest, Npc $npc): void {

        if (!is_null($quest->rewardItem->effect)) {
            $effectType = new ItemEffectsValue($quest->rewardItem->effect);

            if ($effectType->getCopperCoins()) {
                broadcast(new GlobalMessageEvent('Lighting streaks across the skies, blackness fills the skies. A thunderous roar is heard across the land.'));

                broadcast(new ServerMessageEvent($character->user, 'Careful, child. You seem to have angered The Creator. Are you prepared?'));
            }
        }

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $quest->reward_item,
        ]);

        if (!is_null($quest->rewardItem->devouring_darkness) || !is_null($quest->rewardItem->devouring_light)) {
            $this->updateCharacterAttackDataCache($character);
        }

        $this->npcServerMessage($npc, $character, 'given_item');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->rewardItem->name, $slot->id));
    }

    public function unlockSkill(Quest $quest, Character $character, Npc $npc): void {
        $gameSkill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

        $characterSkill = $character->skills()->where('game_skill_id', $gameSkill->id)->where('is_locked', true)->first();

        $characterSkill->update([
            'is_locked' => false
        ]);

        $character = $character->refresh();

        $this->updateCharacterAttackDataCache($character);

        $this->npcServerMessage($npc, $character, 'skill_unlocked');

        event(new ServerMessageEvent($character->user, 'Unlocked: ' . $gameSkill->name . ' This skill can now be leveled!'));

        event(new UnlockSkillEvent($character->user));

        event(new UpdateCharacterStatus($character));
    }

    public function giveGold(Character $character, Quest $quest, Npc $npc): void {

        $newValue = $character->gold + $quest->reward_gold;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::GOLD))->canNotGiveCurrency()) {
            $newValue = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold) . ' gold from: ' . $npc->real_name));
    }

    public function giveGoldDust(Character $character, Quest $quest, Npc $npc): void {

        $newValue = $character->gold_dust + $quest->reward_gold_dust;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::GOLD_DUST))->canNotGiveCurrency()) {
            $newValue = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold_dust) . ' gold dust from: ' . $npc->real_name));
    }

    public function giveShards(Character $character, Quest $quest, Npc $npc): void {

        $newValue = $character->shards + $quest->reward_shards;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::SHARDS))->canNotGiveCurrency()) {
            $newValue = MaxCurrenciesValue::MAX_SHARDS;
        }

        $character->update([
            'shards' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_shards) . ' shards from: ' . $npc->real_name));
    }

    public function npcServerMessage(Npc $npc, Character $character, string $type): void {
        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build($type, $npc)));
    }

    public function updateCharacterAttackDataCache(Character $character): void {
        CharacterAttackTypesCacheBuilder::dispatch($character);
    }

    public function createQuestLog(Character $character, Quest $quest): void {
        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        broadcast(new ServerMessageEvent($character->user, 'Quest: ' . $quest->name . ' completed. Check quest logs under adventure logs section.'));

        event(new UpdateTopBarEvent($character));
    }
}
