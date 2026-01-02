<?php

namespace App\Game\Quests\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Factions\FactionLoyalty\Services\UpdateFactionLoyaltyService;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\Concerns\BaseMessageType;
use App\Game\Messages\Types\NpcMessageTypes;
use App\Game\Quests\Events\UnlockSkillEvent;

class NpcQuestRewardHandler
{
    use HandleCharacterLevelUp;

    public function __construct(
        private readonly NpcServerMessageBuilder $npcServerMessageBuilder,
        private readonly UpdateFactionLoyaltyService $updateFactionLoyaltyService) {}

    public function processReward(Quest $quest, Npc $npc, Character $character): void
    {

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

        if ($this->questRewardsPassive($quest)) {
            $passive = $character->passiveSkills()->where('passive_skill_id', $quest->unlocks_passive_id)->first();

            $passive->update([
                'is_locked' => false,
            ]);

            $passive = $passive->refresh();

            event(new ServerMessageEvent($character->user, 'You unlocked a new Kingdom passive! head to your skills section
            to learn more. You have unlocked: '.$passive->passiveSkill->name));
        }

        if (! is_null($quest->unlocks_feature)) {

            if ($quest->unlocksFeature()->isReincarnation()) {

                event(new ServerMessageEvent($character->user, 'You can now reincarnate your character for a cost of 50,000 Copper Coins per Reincarnation. This allows
                you to set your character level back to level 1 and keep 20% of your base stats, but you are penalized by having 5% (that stacks per reincarnation)
                added to your XP. You can now use this feature on your Character Sheet!'));

                GlobalMessageEvent::dispatch($character->name.' has unlocked: Reincarnation! A powerful new way to grow!');
            }

            if ($quest->unlocksFeature()->isCosmeticText()) {
                event(new ServerMessageEvent($character->user, 'You can now use a new feature in your settings (Hover/Tap Top Right User Icon) to change your chat text
                to a color of your choice as well as italize or bold your text for public chat messages. How exciting!'));

                event(new GlobalMessageEvent($character->name.' Has unlocked an epic gift! Cosmetic Text! They can truly stand out from the rest of you now!'));
            }

            if ($quest->unlocksFeature()->isCosmeticNameTag()) {
                event(new ServerMessageEvent($character->user, 'You can now select name tags fro your character to show off in chat by selecting one from your settings page (Hover/Tap To Right User Icon) to change the tag
                that shows beside your character name in chat!'));

                event(new GlobalMessageEvent($character->name.' Has unlocked an epic gift! Name Tags! Their deeds have not gone unnoticed in the land of Tlessa!'));
            }

            if ($quest->unlocksFeature()->isExtendSets()) {

                $this->giveAdditionalSetsToCharacter($character);

                event(new ServerMessageEvent($character->user, 'You now have an additional 10 sets. If you head to your Character Sheet, and visit your inventory, under the sets tab you have ten additional sets you can add items to.'));

                event(new GlobalMessageEvent($character->name.' Has unlocked an epic gift! 10 additional sets! Their deeds have not gone unnoticed in the land of Tlessa!'));
            }

            if ($quest->unlocksFeature()->isExtendedBackpack()) {
                $character->update(['inventory_max' => 150]);

                event(new ServerMessageEvent($character->user, 'Your inventory has been increased from a max of 75 slots to 150 slots. You can now carry more.'));

                event(new GlobalMessageEvent($character->name.' Has unlocked an epic gift! They now have more inventory slots up from 75 to a new max of 150!'));
            }
        }
    }

    public function questHasRewardItem(Quest $quest): bool
    {
        return ! is_null($quest->reward_item);
    }

    public function questUnlocksSkill(Quest $quest): bool
    {
        return $quest->unlocks_skill;
    }

    public function questRewardsGold(Quest $quest): bool
    {
        return ! is_null($quest->reward_gold);
    }

    public function questRewardsGoldDust(Quest $quest): bool
    {
        return ! is_null($quest->reward_gold_dust);
    }

    public function questRewardsShards(Quest $quest): bool
    {
        return ! is_null($quest->reward_shards);
    }

    public function questRewardsXP(Quest $quest): bool
    {
        return ! is_null($quest->reward_xp);
    }

    public function questRewardsPassive(Quest $quest): bool
    {
        return ! is_null($quest->unlocks_passive_id);
    }

    public function giveXP(Character $character, Quest $quest): void
    {
        $character->update([
            'xp' => $quest->reward_xp,
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);
    }

    public function giveItem(Character $character, Quest $quest, Npc $npc): void
    {

        if (! is_null($quest->rewardItem->effect)) {
            $effectType = new ItemEffectsValue($quest->rewardItem->effect);

            if ($effectType->getCopperCoins()) {
                broadcast(new GlobalMessageEvent('Lighting streaks across the skies, blackness fills the skies. A thunderous roar is heard across the land.'));

                broadcast(new ServerMessageEvent($character->user, 'Careful, child. You seem to have angered The Creator. Are you prepared?'));
            }

            if ($effectType->purgatory()) {
                $this->updateFactionLoyaltyService->updateFactionLoyaltyBountyTasks($character);
            }
        }

        $foundQuestitem = $character->inventory->slots->filter(function ($slot) use ($quest) {
            return $slot->item_id === $quest->reward_item;
        })->first();

        if (! is_null($foundQuestitem)) {
            broadcast(new ServerMessageEvent($character->user, 'You already own the: '.$quest->rewardItem->name.' You shall not recieve another.'));

            return;
        }

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $quest->reward_item,
        ]);

        if (! is_null($quest->rewardItem->devouring_darkness) || ! is_null($quest->rewardItem->devouring_light)) {
            $this->updateCharacterAttackDataCache($character);
        }

        $this->npcServerMessage($npc, $character, NpcMessageTypes::GIVE_ITEM);

        broadcast(new ServerMessageEvent($character->user, 'Received: '.$quest->rewardItem->name, $slot->id));
    }

    public function unlockSkill(Quest $quest, Character $character, Npc $npc): void
    {
        $gameSkill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

        $characterSkill = $character->skills()->where('game_skill_id', $gameSkill->id)->where('is_locked', true)->first();

        $characterSkill->update([
            'is_locked' => false,
        ]);

        $character = $character->refresh();

        $this->updateCharacterAttackDataCache($character);

        $this->npcServerMessage($npc, $character, NpcMessageTypes::SKILL_UNLOCKED);

        event(new ServerMessageEvent($character->user, 'Unlocked: '.$gameSkill->name.' This skill can now be leveled!'));

        event(new UnlockSkillEvent($character->user));

        event(new UpdateCharacterStatus($character));
    }

    public function giveGold(Character $character, Quest $quest, Npc $npc): void
    {

        $newValue = $character->gold + $quest->reward_gold;

        if ($newValue > MaxCurrenciesValue::MAX_GOLD) {
            $newValue = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newValue,
        ]);

        $this->npcServerMessage($npc, $character, NpcMessageTypes::CURRENCY_GIVEN);

        broadcast(new ServerMessageEvent($character->user, 'Received: '.number_format($quest->reward_gold).' gold from: '.$npc->real_name));
    }

    public function giveGoldDust(Character $character, Quest $quest, Npc $npc): void
    {

        $newValue = $character->gold_dust + $quest->reward_gold_dust;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::GOLD_DUST))->canNotGiveCurrency()) {
            $newValue = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newValue,
        ]);

        $this->npcServerMessage($npc, $character, NpcMessageTypes::CURRENCY_GIVEN);

        broadcast(new ServerMessageEvent($character->user, 'Received: '.number_format($quest->reward_gold_dust).' gold dust from: '.$npc->real_name));
    }

    public function giveShards(Character $character, Quest $quest, Npc $npc): void
    {

        $newValue = $character->shards + $quest->reward_shards;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::SHARDS))->canNotGiveCurrency()) {
            $newValue = MaxCurrenciesValue::MAX_SHARDS;
        }

        $character->update([
            'shards' => $newValue,
        ]);

        $this->npcServerMessage($npc, $character, NpcMessageTypes::CURRENCY_GIVEN);

        broadcast(new ServerMessageEvent($character->user, 'Received: '.number_format($quest->reward_shards).' shards from: '.$npc->real_name));
    }

    public function npcServerMessage(Npc $npc, Character $character, BaseMessageType $type): void
    {
        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build($type, $npc)));
    }

    public function updateCharacterAttackDataCache(Character $character): void
    {
        CharacterAttackTypesCacheBuilder::dispatch($character);
    }

    public function createquestQuestLog(Character $character, Quest $quest): void
    {
        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $character = $character->refresh();

        broadcast(new ServerMessageEvent($character->user, 'Quest: '.$quest->name.' completed. Check quest logs under quest logs section.'));

        event(new UpdateCharacterBaseDetailsEvent($character));
    }

    private function giveAdditionalSetsToCharacter(Character $character): Character
    {
        for ($i = 1; $i <= 10; $i++) {
            $character->inventorySets()->create([
                'character_id' => $this->character->id,
                'can_be_equipped' => true,
            ]);

            $character = $character->refresh();
        }

        return $character;
    }
}
