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

    public function questRewardHandler(): NpcQuestRewardHandler {
        return $this->npcQuestRewardHandler;
    }

    public function handleNpcQuest(Character $character, Quest $quest): void
    {
        $this->consumeQuestRequirements($character, $quest);
        $this->npcQuestRewardHandler->processNonXpRewards($quest, $quest->npc, $character);
    }

    public function consumeQuestRequirements(Character $character, Quest $quest): void
    {
        $requiredItem = null;
        $secondaryRequiredItem = null;

        if ($this->questRequiresItem($quest)) {
            $requiredItem = $this->fetchRequiredItem($quest, $character);

            if (is_null($requiredItem)) {
                throw new Exception('The required quest item is missing.');
            }
        }

        if ($this->questRequiresSecondaryItem($quest)) {
            $secondaryRequiredItem = $this->fetchSecondaryRequiredItem($quest, $character);

            if (is_null($secondaryRequiredItem)) {
                throw new Exception('The secondary required quest item is missing.');
            }
        }

        if ($this->questHasCurrenciesRequirement($quest) && ! $this->canPay($character, $quest)) {
            throw new Exception('The required quest currencies are missing.');
        }

        if ($this->questRequiresPlaneAccess($quest) && ! $this->hasPlaneAccess($quest, $character)) {
            throw new Exception('The required plane access is missing.');
        }

        if ($this->questHasFactionRequirement($quest) && ! $this->hasMetFactionRequirement($character, $quest)) {
            throw new Exception('The required faction level is missing.');
        }

        if ($this->questHasFactionLoyaltyRequirement($quest) && ! $this->hasMetFactionLoyaltyRequirements($quest, $character)) {
            throw new Exception('The required faction loyalty is missing.');
        }

        if (! is_null($requiredItem)) {
            $requiredItem->delete();
        }

        if (! is_null($secondaryRequiredItem)) {
            $secondaryRequiredItem->delete();
        }

        if ($this->questHasCurrenciesRequirement($quest)) {
            $this->payCurrencies($character, $quest);
        }
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
