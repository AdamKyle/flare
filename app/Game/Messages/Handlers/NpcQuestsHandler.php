<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Values\FactionLevel;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Collection;

class NpcQuestsHandler {

    private $npcServerMessageBuilder;

    private $npcQuestRewardHandler;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder, NpcQuestRewardHandler $npcQuestRewardHandler) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
        $this->npcQuestRewardHandler   = $npcQuestRewardHandler;
    }

    public function handleNpcQuests(Character $character, Npc $npc): bool {

        $completedQuests = $character->questsCompleted->pluck('quest_id')->toArray();
        $quests          = $this->fetchQuestsFromNpc($npc, $completedQuests);

        if ($quests->isEmpty()) {
            return false;
        }

        $finishedAtLeastOneQuest = false;

        foreach ($quests as $quest) {

            if ($this->shouldBailOnQuest($character, $npc, $quest, $completedQuests)) {
                continue;
            }

            $giveRewards = false;

            if ($this->questRequiresItem($quest)) {
                $foundItem = $this->fetchRequiredItem($quest, $character);

                if (!is_null($foundItem)) {
                    $foundItem->delete();

                    $this->npcServerMessage($npc, $character, 'taken_item');

                    $giveRewards = true;
                }
            }

            if ($this->questRequiresSecondaryItem($quest)) {
                $secondaryItem = $this->fetchSecondaryRequiredItem($quest, $character);

                if (!is_null($secondaryItem)) {
                    $secondaryItem->delete();

                    $this->npcServerMessage($npc, $character, 'taken_second_item');

                    $giveRewards = true;
                }
            }

            if ($this->questHasCurrencieRequirement($quest)) {
                if ($this->canPay($character, $quest)) {
                    $this->payCurrencies($character, $npc, $quest);

                    $giveRewards = true;
                }
            }

            if ($this->questRequiresPlaneAccess($quest)) {
                if ($this->hasPlaneAccess($quest, $character)) {
                    $this->npcServerMessage($npc, $character, 'has_plane_access');

                    $giveRewards = true;
                }
            }

            if ($this->questHasFactionRequirement($quest)) {
                if ($this->hasMetFactionRequirement($character, $quest)) {
                    $this->npcServerMessage($npc, $character, 'has_faction_level');

                    $giveRewards = true;
                }
            }

            if ($giveRewards) {

                $this->alertAboutCurrencyCapped($character, $npc, $quest);

                $this->npcQuestRewardHandler->processReward($quest, $npc, $character);

                $finishedAtLeastOneQuest = true;
            }
        }

        return $finishedAtLeastOneQuest;
    }

    public function shouldBailOnQuest(Character $character, Npc $npc, Quest $quest, array $completedQuests) {
        if (!$this->validateParentQuest($quest, $completedQuests)) {
            return true;
        }

        if ($this->questRequiresItem($quest)) {
            $foundItem = $this->fetchRequiredItem($quest, $character);

            if (is_null($foundItem)) {
                return true;
            }
        }

        if ($this->questRequiresSecondaryItem($quest)) {
            $secondaryItem = $this->fetchSecondaryRequiredItem($quest, $character);

            if (is_null($secondaryItem)) {
                return true;
            }
        }

        if ($this->questHasCurrencieRequirement($quest)) {
            if (!$this->canPay($character, $quest)) {
                return true;
            }
        }

        if ($this->questRequiresPlaneAccess($quest)) {
            if (!$this->hasPlaneAccess($quest, $character)) {
                return true;
            }
        }

        if ($this->questHasFactionRequirement($quest)) {
            if (!$this->hasMetFactionRequirement($character, $quest)) {
                return true;
            }
        }

        return false;
    }

    public function fetchQuestsFromNpc(Npc $npc, array $completedQuestIds): Collection {
        return $npc->quests()->whereNotIn('id', $completedQuestIds)->get();
    }

    public function validateParentQuest($quest, array $completedQuestIds) {
        if ($this->doesQuestHaveParent($quest)) {
            if (!$this->isParentQuestComplete($quest, $completedQuestIds)) {
                return false;
            }
        }

        return true;
    }

    public function doesQuestHaveParent(Quest $quest): bool {
        return !is_null($quest->parent);
    }

    public function isParentQuestComplete(Quest $quest, array $completedQuestIds): bool {
        return in_array($quest->parent->id, $completedQuestIds);
    }

    public function questRequiresItem(Quest $quest): bool {
        return !is_null($quest->item);
    }

    public function questRequiresSecondaryItem(Quest $quest): bool {
        return !is_null($quest->secondaryItem);
    }

    public function questRequiresPlaneAccess(Quest $quest): bool {
        return !is_null($quest->access_to_map_id);
    }

    public function questHasCurrencieRequirement(Quest $quest): bool {
        return $quest->gold_dust_cost > 0 || $quest->gold_cost > 0 || $quest->shard_cost > 0;
    }

    public function questHasFactionRequirement(Quest $quest): bool {
        return !is_null($quest->faction_game_map_id);
    }

    public function fetchRequiredItem(Quest $quest, Character $character): ?InventorySlot {
        return $character->inventory->slots->filter(function($slot) use ($quest) {
            return $slot->item_id === $quest->item_id;
        })->first();
    }

    public function fetchSecondaryRequiredItem(Quest $quest, Character $character): ?InventorySlot {
        return $character->inventory->slots->filter(function($slot) use ($quest) {
            return $slot->item_id === $quest->secondary_required_item;
        })->first();
    }

    public function hasPlaneAccess(Quest $quest, Character $character): bool {
        $itemNeeded = $quest->requiredPlane->map_required_item;

        $planeAccessItem = $character->inventory->slots->filter(function($slot) use($itemNeeded) {
            return $slot->item->effect === $itemNeeded->effect;
        })->first();

        if (is_null($planeAccessItem)) {
            return false;
        }

        return true;
    }

    public function hasMetFactionRequirement(Character $character, Quest $quest): bool {
        $faction = $character->factions->where('game_map_id', $quest->faction_game_map_id)->first();

        if ($quest->required_faction_level > 4) {
            if (!FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {
                return false;
            }
        } else {
            if ($faction->current_level < $quest->required_faction_level) {
                return false;
            }
        }

        return true;
    }

    public function npcServerMessage(Npc $npc, Character $character, string $type): void {
        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build($type, $npc), true));
    }

    public function alertAboutCurrencyCapped(Character $character, Npc $npc, Quest $quest) {
        $newGold          = $character->gold + $quest->reward_gold;
        $newGoldDust      = $character->gold_dust + $quest->reward_gold_dust;
        $newShards        = $character->shards + $quest->reward_shards;

        $maxGoldValue     = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);
        $maxGoldDustValue = new MaxCurrenciesValue($newGoldDust, MaxCurrenciesValue::GOLD_DUST);
        $maxShardsValue   = new MaxCurrenciesValue($newShards, MaxCurrenciesValue::SHARDS);

        if ($maxGoldValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_capped', $npc), true));
        }

        if ($maxGoldDustValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_dust_capped', $npc), true));
        }

        if ($maxShardsValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('shard_capped', $npc), true));
        }
    }

    public function canPay(Character $character, Quest $quest) : bool {
        $canPay = true;

        if (!is_null($quest->gold_cost)) {
            $canPay = $character->gold >= $quest->gold_cost;
        }

        if (!is_null($quest->gold_dust_cost)) {
            $canPay = $character->gold_dust >= $quest->gold_dust_cost;
        }

        if (!is_null($quest->shard_cost)) {
            $canPay = $character->shards >= $quest->shard_cost;
        }

        return $canPay;
    }

    public function payCurrencies(Character $character, Npc $npc, Quest $quest) {
        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('take_currency', $npc), true));

        $newGold     = $character->gold - $quest->gold_cost;
        $newGoldDust = $character->gold_dust - $quest->gold_dust_cost;
        $newShards   = $character->shards - $quest->shard_cost;

        if ($newGold <= 0) {
            $newGold = 0;
        }

        if ($newGoldDust <= 0) {
            $newGoldDust = 0;
        }

        if ($newShards <= 0) {
            $newShards = 0;
        }

        $character->update([
            'gold' => !is_null($quest->gold_cost) ? $newGold : $character->gold,
            'gold_dust' => !is_null($quest->gold_dust_cost) ? $newGoldDust : $character->gold_dust,
            'shards' => !is_null($quest->shard_cost) ? $newShards : $character->shards,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        broadcast(new ServerMessageEvent($character->user, 'You have paid ' . $npc->real_name . ' the required currencies.'));
    }

}
