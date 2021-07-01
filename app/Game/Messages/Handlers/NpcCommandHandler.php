<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcComponentsValue;
use App\Game\Battle\Values\MaxCurrenciesValue;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Broadcasting\PendingBroadcast;

class NpcCommandHandler {

    use KingdomCache;

    /**
     * @var NpcServerMessageBuilder $npcServerMessageBuilder
     */
    private $npcServerMessageBuilder;

    /**
     * KINGDOM_COST
     */
    private const KINGDOM_COST = 10000;

    /**
     * NpcCommandHandler constructor.
     *
     * @param NpcServerMessageBuilder $npcServerMessageBuilder
     */
    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    /**
     * Handle the command.
     *
     * @param int $type
     * @param string $npcName
     * @param User $user
     * @return PendingBroadcast
     * @throws Exception
     */
    public function handleForType(int $type, Npc $npc, User $user) {
        $type        = new NpcCommandTypes($type);
        $message     = null;
        $messageType = null;

        if ($user->character->is_dead) {
            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('dead', $npc), true));
        }

        if (!$user->character->can_adventure) {
            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('adventuring', $npc), true));
        }

        if ($npc->must_be_at_same_location) {
            $character = $user->character;

            if ($character->x_position !== $npc->x_position && $character->y_position !== $npc->y_position) {
                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('location', $npc), true));
            }
        }

        if ($type->isTakeKingdom()) {
            if ($this->handleTakingKingdom($user, $npc->name)) {
                $message     = $user->character->name . ' Has paid The Old Man for a kingdom on the ' . $user->character->map->gameMap->name . ' plane.';
                $messageType = 'took_kingdom';
            }
        }

        if ($type->isConjure()) {

            broadcast(new NpcComponentShowEvent($user, NpcComponentsValue::CONJURE));

            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('take_a_look', $npc), true));
        }

        if ($type->isQuest()) {
            if ($this->handleQuest($user, $npc)) {
                $message     = $user->character->name . ' has completed a quest for: ' . $npc->real_name . ' And has been rewarded with a godly gift!';
                $messageType = 'quest_complete';
            } else {
                $messageType = 'no_quests';
            };
        }

        broadcast(new GlobalMessageEvent($message));

        return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build($messageType, $npc), true));
    }

    /**
     * Handles taking the kingdom.
     *
     * @param User $user
     * @param string $npcName
     * @return bool
     */
    protected function handleTakingKingdom(User $user, string $npcName): bool {
        $character      = $user->character;
        $characterX     = $character->map->character_position_x;
        $characterY     = $character->map->character_position_y;
        $characterMapId = $character->map->game_map_id;
        $tookKingdom    = false;

        $kingdom = Kingdom::whereNull('character_id')
                          ->where('x_position', $characterX)
                          ->where('y_position', $characterY)
                          ->where('game_map_id', $characterMapId)
                          ->where('npc_owned', true)
                          ->first();

        if (is_null($kingdom)) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('cannot_have', $npcName), true));
        } else {
            $gold         = $character->gold;
            $kingdomCount = $character->kingdoms()->where('game_map_id', $character->map->game_map_id)->count();
            $cost         = ($kingdomCount * self::KINGDOM_COST);

            if ($gold < $cost) {
                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('not_enough_gold', $npcName), true));
            } else {
                $character->update([
                    'gold' => $gold - $cost,
                ]);

                event(new UpdateTopBarEvent($character->refresh()));
            }

            $kingdom->update([
                'character_id' => $character->id
            ]);

            $this->addKingdomToCache($character->refresh(), $kingdom->refresh());

            event(new AddKingdomToMap($character));
            event(new UpdateGlobalMap($character));
            event(new UpdateNPCKingdoms($character->map->gameMap));

            $tookKingdom = true;
        }

        return $tookKingdom;
    }

    protected function handleQuest($user, $npc) {
        $character         = $user->character;
        $completedQuestIds = $character->questsCompleted->pluck('id')->toArray();

        $quests = $npc->quests()->whereNotIn('id', $completedQuestIds)->get();

        if ($quests->isEmpty()) {
            return false;
        }

        foreach ($quests as $quest) {
            if (!is_null($quest->item_id)) {
                $foundItem = $character->inventory->slots->filter(function($slot) use ($quest) {
                    return $slot->item_id === $quest->item_id;
                })->first();

                if (is_null($foundItem)) {
                    continue;
                }

                if ($this->handleReward($character, $quest, $npc)) {
                    $foundItem->delete();

                    broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('taken_item', $npc->name), true));

                    return true;
                }
            }
        }

        return false;
    }

    private function handleReward(Character $character, Quest $quest, Npc $npc) {

        if (!is_null($quest->reward_item)) {
            if ($character->inventory_max < $character->inventory->slots()->count()) {
                $character->inventory->slots()->create([
                    'inventory_id' => $character->inventory->id,
                    'item_id'      => $quest->reward_item,
                ]);

                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('given_item', $npc->name), true));
            } else {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('inventory_full', $npc->name), true));
                return false;
            }
        }

        if (!is_null($quest->reward_gold)) {
            $maxCurrenciesValue = new MaxCurrenciesValue($character->gold, 0);

            if ($maxCurrenciesValue->canNotGiveCurrency()) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_capped', $npc->name), true));
                return false;
            } else {
                $character->update([
                    'gold' => $character->gold + $quest->reward_gold
                ]);

                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc->name), true));
                broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->reward_gold . ' gold from: ' . $npc->real_name));
            }
        }

        if (!is_null($quest->reward_gold_dust)) {
            $maxCurrenciesValue = new MaxCurrenciesValue($character->gold_dust, 1);

            if ($maxCurrenciesValue->canNotGiveCurrency()) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_dust_capped', $npc->name), true));
                return false;
            } else {
                $character->update([
                    'gold_dust' => $character->gold_dust + $quest->reward_gold_dust
                ]);

                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc->name), true));
                broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->reward_gold_dust . ' gold dust from: ' . $npc->real_name));
            }
        }

        if (!is_null($quest->reward_shards)) {
            $maxCurrenciesValue = new MaxCurrenciesValue($character->shards, 2);

            if ($maxCurrenciesValue->canNotGiveCurrency()) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('shard_capped', $npc->name), true));
                return false;
            } else {
                $character->update([
                    'gold_dust' => $character->gold_dust + $quest->reward_shards
                ]);

                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc->name), true));
                broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->reward_shards . ' shards from: ' . $npc->real_name));
            }
        }

        if ($quest->unlocks_skill) {
            $gameSkill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

            if (!is_null($gameSkill)) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('no_skill', $npc->name), true));
                return false;
            } else {
                $characterSkill = $character->skill()->where('game_skill_id', $gameSkill->id)->where('is_locked', true)->first();

                if (is_null($characterSkill)) {
                    broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('dont_own_skill', $npc->name), true));
                    return false;
                }

                $characterSkill->update([
                    'is_locked' => false
                ]);
            }
        }

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        broadcast(new ServerMessageEvent('Quest: ' . $quest->name . ' completed. Check quest logs under adventure logs section.'));

        event(new UpdateTopBarEvent($character->refresh()));

        return true;
    }
}
