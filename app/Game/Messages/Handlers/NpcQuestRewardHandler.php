<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class NpcQuestRewardHandler {

    private $npcServerMessageBuilder;

    private $characterAttackTransformer;

    private $buildCharacterAttackTypes;

    private $manager;

    public function __construct(NpcServerMessageBuilder    $npcServerMessageBuilder,
                                CharacterAttackTransformer $characterAttackTransformer,
                                BuildCharacterAttackTypes  $buildCharacterAttackTypes,
                                Manager                    $manager
    ) {
        $this->npcServerMessageBuilder    = $npcServerMessageBuilder;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->buildCharacterAttackTypes  = $buildCharacterAttackTypes;
        $this->manager                    = $manager;
    }

    public function processReward(Quest $quest, Npc $npc, Character $character) {

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

    public function giveItem(Character $character, Quest $quest, Npc $npc) {
        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $quest->reward_item,
        ]);

        if (!is_null($quest->rewardItem->devouring_darkness) || !is_null($quest->rewardItem->devouring_light)) {
            $this->updateCharacterAttakDataCache($character);
        }

        $this->npcServerMessage($npc, $character, 'given_item');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->rewardItem->name, false));
    }

    public function unlockSkill(Quest $quest, Character $character, Npc $npc) {
        $gameSkill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

        $characterSkill = $character->skills()->where('game_skill_id', $gameSkill->id)->where('is_locked', true)->first();

        $characterSkill->update([
            'is_locked' => false
        ]);

        $this->updateCharacterAttakDataCache($character->refresh());

        $this->npcServerMessage($npc, $character, 'skill_unlocked');

        broadcast(new ServerMessageEvent($character->user, 'Unlocked: ' . $gameSkill->name . ' This skill can now be leveled!'));
    }

    public function giveGold(Character $character, Quest $quest, Npc $npc) {

        $newValue = $character->gold + $quest->reward_gold;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::GOLD))->canNotGiveCurrency()) {
            return;
        }

        $character->update([
            'gold' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold) . ' gold from: ' . $npc->real_name));
    }

    public function giveGoldDust(Character $character, Quest $quest, Npc $npc) {

        $newValue = $character->gold_dust + $quest->reward_gold_dust;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::GOLD_DUST))->canNotGiveCurrency()) {
            return;
        }

        $character->update([
            'gold_dust' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold_dust) . ' gold dust from: ' . $npc->real_name));
    }

    public function giveShards(Character $character, Quest $quest, Npc $npc) {

        $newValue = $character->shards + $quest->reward_shards;

        if ((new MaxCurrenciesValue($newValue, MaxCurrenciesValue::SHARDS))->canNotGiveCurrency()) {
            return;
        }

        $character->update([
            'shards' => $newValue
        ]);

        $this->npcServerMessage($npc, $character, 'currency_given');

        broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_shards) . ' shards from: ' . $npc->real_name));
    }

    public function npcServerMessage(Npc $npc, Character $character, string $type): void {
        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build($type, $npc), true));
    }

    public function updateCharacterAttakDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new Item($character->refresh(), $this->characterAttackTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($characterData, $character->user));
    }

    public function createQuestLog(Character $character, Quest $quest) {
        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        broadcast(new ServerMessageEvent($character->user, 'Quest: ' . $quest->name . ' completed. Check quest logs under adventure logs section.'));

        event(new UpdateTopBarEvent($character));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new CharacterInventoryDetailsUpdate($character->user));
    }
}