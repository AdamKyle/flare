<?php

namespace App\Game\Quests\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Quests\Traits\QuestDetails;
use Exception;

class NpcQuestsHandler
{
    use QuestDetails;

    private $npcServerMessageBuilder;

    private $npcQuestRewardHandler;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder, NpcQuestRewardHandler $npcQuestRewardHandler)
    {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
        $this->npcQuestRewardHandler = $npcQuestRewardHandler;
    }

    public function handleNpcQuest(Character $character, Quest $quest): void
    {
        $npc = $quest->npc;

        $giveRewards = false;

        if ($this->questRequiresItem($quest)) {
            $foundItem = $this->fetchRequiredItem($quest, $character);

            if (! is_null($foundItem)) {
                $foundItem->delete();

                $giveRewards = true;
            }
        }

        if ($this->questRequiresSecondaryItem($quest)) {
            $secondaryItem = $this->fetchSecondaryRequiredItem($quest, $character);

            if (! is_null($secondaryItem)) {
                $secondaryItem->delete();

                $giveRewards = true;
            }
        }

        if ($this->questHasCurrenciesRequirement($quest)) {
            if ($this->canPay($character, $quest)) {
                $this->payCurrencies($character, $quest);

                $giveRewards = true;
            }
        }

        if ($this->questRequiresPlaneAccess($quest)) {
            if ($this->hasPlaneAccess($quest, $character)) {

                $giveRewards = true;
            }
        }

        if ($this->questHasFactionRequirement($quest)) {
            if ($this->hasMetFactionRequirement($character, $quest)) {

                $giveRewards = true;
            }
        }

        if ($this->questHasFactionLoyaltyRequirement($quest)) {
            if ($this->hasMetFactionLoyaltyRequirements($quest, $character)) {

                $giveRewards = true;
            }
        }

        if ($giveRewards) {
            $this->npcQuestRewardHandler->processReward($quest, $npc, $character);

            return;
        }

        throw new Exception($quest->npc->real_name.' thinks The Creator forgot to tell them how to handle this quest!');
    }

    public function payCurrencies(Character $character, Quest $quest)
    {
        $newGold = $character->gold - $quest->gold_cost;
        $newGoldDust = $character->gold_dust - $quest->gold_dust_cost;
        $newShards = $character->shards - $quest->shard_cost;
        $newCopperCoins = $character->copper_coins - $quest->copper_coin_cost;

        if ($newGold <= 0) {
            $newGold = 0;
        }

        if ($newGoldDust <= 0) {
            $newGoldDust = 0;
        }

        if ($newShards <= 0) {
            $newShards = 0;
        }

        if ($newCopperCoins <= 0) {
            $newCopperCoins = 0;
        }

        $character->update([
            'gold' => ! is_null($quest->gold_cost) ? $newGold : $character->gold,
            'gold_dust' => ! is_null($quest->gold_dust_cost) ? $newGoldDust : $character->gold_dust,
            'shards' => ! is_null($quest->shard_cost) ? $newShards : $character->shards,
            'copper_coins' => ! is_null($quest->copper_coin_cost) ? $newCopperCoins : $character->copper_coins,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }
}
