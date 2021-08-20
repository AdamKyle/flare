<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
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
use App\Game\Maps\Events\UpdateActionsBroadcast;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Broadcasting\PendingBroadcast;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

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
        MonsterTransfromer         $monsterTransformer,
        Manager                    $manager,
    ) {
        $this->npcServerMessageBuilder    = $npcServerMessageBuilder;
        $this->characterAttackTransformer = $characterAttackTransformer;
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

        if ($type->isQuest()) {
            if ($this->handleQuest($user, $npc)) {
                $message     = $user->character->name . ' has completed a quest for: ' . $npc->real_name . ' And has been rewarded with a godly gift!';
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
            if (!is_null($quest->item_id)) {
                $foundItem = $character->inventory->slots->filter(function($slot) use ($quest) {
                    return $slot->item_id === $quest->item_id;
                })->first();

                if (is_null($foundItem)) {
                    continue;
                }

                if (!$this->canHaveReward($character, $npc)) {
                    return false;
                }

                $foundItem->delete();

                broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('taken_item', $npc), true));

                $this->handleReward($character, $quest, $npc);

                return true;
            }
        }

        return false;
    }

    private function canHaveReward(Character $character, Npc $npc) {
        if ($character->isInventoryFull()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('inventory_full', $npc), true));
            return false;
        }

        $maxGoldValue     = new MaxCurrenciesValue($character->gold, 0);
        $maxGoldDustValue = new MaxCurrenciesValue($character->gold_dust, 1);
        $maxShardsValue   = new MaxCurrenciesValue($character->shards, 2);

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

                $this->updateActions($character->map->game_map_id, $character);

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

        event(new UpdateTopBarEvent($character->refresh()));

        return true;
    }

    protected function updateActions(int $mapId, Character $character) {
        $user      = $character->user;
        $character = new Item($character->refresh(), $this->characterAttackTransformer);
        $monsters  = new Collection(
            Monster::where('published', true)
                   ->where('game_map_id', $mapId)
                   ->where('is_celestial_entity', false)
                   ->orderBy('max_level', 'asc')->get(),
            $this->monsterTransformer
        );

        $character = $this->manager->createData($character)->toArray();
        $monster   = $this->manager->createData($monsters)->toArray();

        broadcast(new UpdateActionsBroadcast($character, $monster, $user));
    }
}
