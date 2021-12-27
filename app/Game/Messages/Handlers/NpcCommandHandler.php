<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\GameMap;
use App\Game\Core\Events\ResetQuestStorageBroadcastEvent;
use App\Game\Core\Values\FactionLevel;
use Exception;
use Illuminate\Broadcasting\PendingBroadcast;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Item as ResourceItem;
use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcComponentsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class NpcCommandHandler {

    use KingdomCache;

    /**
     * @var NpcServerMessageBuilder $npcServerMessageBuilder
     */
    private $npcServerMessageBuilder;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private $characterAttackTransformer;

    private $buildCharacterAttackTypes;

    /**
     * @var MonsterTransfromer $monsterTransformer
     */
    private $monsterTransformer;

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * KINGDOM_COST
     */
    private const KINGDOM_COST = 10000;

    /**
     * NpcCommandHandler constructor.
     *
     * @param NpcServerMessageBuilder $npcServerMessageBuilder
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @param MonsterTransfromer $monsterTransformer
     * @param Manager $manager
     */
    public function __construct(
        NpcServerMessageBuilder    $npcServerMessageBuilder,
        CharacterAttackTransformer $characterAttackTransformer,
        BuildCharacterAttackTypes  $buildCharacterAttackTypes,
        MonsterTransfromer         $monsterTransformer,
        Manager                    $manager,
    ) {
        $this->npcServerMessageBuilder    = $npcServerMessageBuilder;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->buildCharacterAttackTypes  = $buildCharacterAttackTypes;
        $this->monsterTransformer         = $monsterTransformer;
        $this->manager                    = $manager;
    }

    /**
     * Handle the command.
     *
     * @param int $type
     * @param Npc $npc
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
            if ($this->handleTakingKingdom($user, $npc)) {
                $message     = $user->character->name . ' Has paid The Old Man for a kingdom on the ' . $user->character->map->gameMap->name . ' plane.';
                $messageType = 'took_kingdom';
            }
        }

        if ($type->isConjure()) {

            broadcast(new NpcComponentShowEvent($user, NpcComponentsValue::CONJURE));

            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('take_a_look', $npc), true));
        }

        if ($type->isReRoll()) {
            broadcast(new NpcComponentShowEvent($user, NpcComponentsValue::ENCHANT));

            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('what_do_you_want', $npc), true));
        }

        if ($type->isQuest()) {
            if ($this->handleQuest($user, $npc)) {
                $message     = $user->character->name . ' has completed a quest for: ' . $npc->real_name . ' and has been rewarded with a godly gift!';
                $messageType = 'quest_complete';
            } else {
                $messageType = 'no_quests';
            };
        }

        if (!is_null($message) && !is_null($messageType)) {
            broadcast(new GlobalMessageEvent($message));

            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build($messageType, $npc), true));
        } else if (!is_null($messageType)) {
            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build($messageType, $npc), true));
        }
    }

    /**
     * Handles taking the kingdom.
     *
     * @param User $user
     * @param Npc $npc
     * @return bool
     */
    protected function handleTakingKingdom(User $user, Npc $npc): bool {
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
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('cannot_have', $npc), true));
        } else {
            $gold         = $character->gold;
            $kingdomCount = $character->kingdoms()->where('game_map_id', $character->map->game_map_id)->count();
            $cost         = ($kingdomCount * self::KINGDOM_COST);

            if ($gold < $cost) {
                broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('not_enough_gold', $npc), true));

                return false;
            } else {
                $character->update([
                    'gold' => $gold - $cost,
                ]);

                event(new UpdateTopBarEvent($character->refresh()));
            }

            $kingdom->update([
                'character_id' => $character->id,
                'npc_owned'    => false,
                'last_walked'  => now(),
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
        $completedQuestIds = $character->questsCompleted->pluck('quest_id')->toArray();

        $quests = $npc->quests()->whereNotIn('id', $completedQuestIds)->get();

        if ($quests->isEmpty()) {
            return false;
        }

        foreach ($quests as $quest) {

            if (!is_null($quest->parent)) {
                $foundParent = $character->questsCompleted()->where('quest_id', $quest->parent->id)->first();


                if (is_null($foundParent)) {
                    broadcast(new ServerMessageEvent($character->user, 'The NPC will not accept this quest from you until you have completed: ' . $quest->parent->name . '. You can find the relevant details under the Plane Quests/All Quests section.'));

                    return false;
                }
            }

            if (!is_null($quest->item_id)) {
                $foundItem = $character->inventory->slots->filter(function($slot) use ($quest) {
                    return $slot->item_id === $quest->item_id;
                })->first();

                if (is_null($foundItem)) {
                    continue;
                }

                $secondaryItem = null;

                if (!is_null($quest->secondary_required_item)) {
                    $secondaryItem = $character->inventory->slots->filter(function($slot) use ($quest) {
                        return $slot->item_id === $quest->secondary_required_item;
                    })->first();

                    if (is_null($secondaryItem)) {
                        continue;
                    }
                }

                if (!$this->canHaveReward($character, $npc, $quest)) {
                    return false;
                }

                if ($quest->gold_dust_cost > 0 || $quest->gold_cost > 0 || $quest->shard_cost > 0) {
                    if (!$this->canPay($character, $quest, $npc)) {
                        return false;
                    }

                    broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('take_currency', $npc), true));

                    $character->update([
                        'gold' => !is_null($quest->gold_cost) ? ($character->gold - $quest->gold_cost) : $character->gold,
                        'gold_dust' => !is_null($quest->gold_dust_cost) ? ($character->gold_dust - $quest->gold_dust_cost) : $character->gold_dust,
                        'shards' => !is_null($quest->shards_cost) ? ($character->shards - $quest->shard_cost) : $character->shards,
                    ]);

                    event(new UpdateTopBarEvent($character->refresh()));

                    broadcast(new ServerMessageEvent($character->user, 'You have paid ' . $npc->real_name . ' the required currencies.'));

                    broadcast(new ServerMessageEvent($character->user, '... one moment please ...'));
                }

                if (!$this->hasPlaneAccess($quest, $character)) {
                    return false;
                }

                if (!$this->hasFactionRequirements($quest, $character)) {
                    return false;
                }

                $foundItem->delete();

                if (!is_null($secondaryItem)) {
                    $secondaryItem->delete();
                }

                broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('taken_item', $npc), true));

                $this->handleReward($character, $quest, $npc);

                return true;
            } else {
                if (!$this->canHaveReward($character, $npc, $quest)) {
                    return false;
                }

                if (!$this->canPay($character, $quest, $npc) && $this->needsToPay($quest)) {
                    return false;
                } else {
                    broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('take_currency', $npc), true));

                    $character->update([
                        'gold' => !is_null($quest->gold_cost) ? $character->gold - $quest->gold_cost : $character->gold,
                        'gold_dust' => !is_null($quest->gold_dust_cost) ? $character->gold_dust - $quest->gold_dust_cost : $character->gold_dust,
                        'shards' => !is_null($quest->shards_cost) ? $character->shards - $quest->shards_cost : $character->shards,
                    ]);

                    event(new UpdateTopBarEvent($character->refresh()));

                    broadcast(new ServerMessageEvent($character->user, 'You have paid ' . $npc->real_name));
                }

                if (!$this->hasPlaneAccess($quest, $character)) {
                    return false;
                }

                if (!$this->hasFactionRequirements($quest, $character)) {
                    return false;
                }

                $this->handleReward($character, $quest, $npc);

                return true;
            }
        }

        return false;
    }

    private function canHaveReward(Character $character, Npc $npc, Quest $quest) {
        if ($character->isInventoryFull()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('inventory_full', $npc), true));
            return false;
        }

        $newGold          = $character->gold + $quest->reward_gold;
        $newGoldDust      = $character->gold_dust + $quest->reward_gold_dust;
        $newShards        = $character->shards + $quest->reward_shards;

        $maxGoldValue     = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);
        $maxGoldDustValue = new MaxCurrenciesValue($newGoldDust, MaxCurrenciesValue::GOLD_DUST);
        $maxShardsValue   = new MaxCurrenciesValue($newShards, MaxCurrenciesValue::SHARDS);

        if ($maxGoldValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_capped', $npc), true));
            return false;
        }

        if ($maxGoldDustValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('gold_dust_capped', $npc), true));
            return false;
        }

        if ($maxShardsValue->canNotGiveCurrency()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('shard_capped', $npc), true));
            return false;
        }

        return true;
    }

    private function canPay(Character $character, Quest $quest, Npc $npc) : bool {
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

        if (!$canPay) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('too_poor', $npc), true));
        }

        return $canPay;
    }

    private function needsToPay(Quest $quest): bool {
        return !is_null($quest->gold_cost) || !is_null($quest->gold_dust_cost) || !is_null($quest->shard_cost);
    }

    private function hasPlaneAccess(Quest $quest, Character $character): bool {
        if (!is_null($quest->access_to_map_id)) {
            $itemNeeded = $quest->requiredPlane->map_required_item;

            $planeAccessItem = $character->inventory->slots->filter(function($slot) use($itemNeeded) {
                return $slot->item->effect === $itemNeeded->effect;
            })->first();

            if (is_null($planeAccessItem)) {
                return false;
            }

            return true;
        }

        return true;
    }

    private function hasFactionRequirements(Quest $quest, Character $character): bool {
        if (!is_null($quest->faction_game_map_id)) {
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
        }

        return true;
    }

    private function handleReward(Character $character, Quest $quest, Npc $npc) {
        if (!is_null($quest->reward_item)) {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $quest->reward_item,
            ]);

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('given_item', $npc), true));

            broadcast(new ServerMessageEvent($character->user, 'Received: ' . $quest->rewardItem->name, false));
        }

        if ($quest->unlocks_skill) {
            $gameSkill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

            if (is_null($gameSkill)) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('no_skill', $npc), true));
                return false;
            } else {
                $characterSkill = $character->skills()->where('game_skill_id', $gameSkill->id)->where('is_locked', true)->first();

                if (is_null($characterSkill)) {
                    broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('dont_own_skill', $npc), true));
                    return false;
                }

                $characterSkill->update([
                    'is_locked' => false
                ]);

                $characterData = new Item($character->refresh(), $this->characterAttackTransformer);
                event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

                $this->updateCharacterAttakDataCache($character->refresh());

                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('skill_unlocked', $npc), true));
                broadcast(new ServerMessageEvent($character->user, 'Unlocked: ' . $gameSkill->name . ' This skill can now be leveled!'));
            }
        }

        if (!is_null($quest->reward_gold)) {
            $character->update([
                'gold' => $character->gold + $quest->reward_gold
            ]);

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc), true));
            broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold) . ' gold from: ' . $npc->real_name));
        }

        if (!is_null($quest->reward_xp)) {
            $xp = (new MaxLevel($character->level, $quest->reward_xp))->fetchXP();

            $character->update([
                'xp' => $character->xp + $xp,
            ]);

            event(new CharacterLevelUpEvent($character->refresh()));

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('xp_given', $npc), true));
            broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_xp) . ' XP from: ' . $npc->real_name));
        }

        if (!is_null($quest->reward_gold_dust)) {
            $character->update([
                'gold_dust' => $character->gold_dust + $quest->reward_gold_dust
            ]);

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc), true));
            broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_gold_dust) . ' gold dust from: ' . $npc->real_name));
        }

        if (!is_null($quest->reward_shards)) {
            $character->update([
                'shards' => $character->shards + $quest->reward_shards
            ]);

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('currency_given', $npc), true));
            broadcast(new ServerMessageEvent($character->user, 'Received: ' . number_format($quest->reward_shards) . ' shards from: ' . $npc->real_name));
        }

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        broadcast(new ServerMessageEvent($character->user, 'Quest: ' . $quest->name . ' completed. Check quest logs under adventure logs section.'));

        broadcast(new ResetQuestStorageBroadcastEvent($character->user));

        event(new UpdateTopBarEvent($character->refresh()));

        return true;
    }

    protected function updateCharacterAttakDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterAttackTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateAttackStats($characterData, $character->user));
    }
}
