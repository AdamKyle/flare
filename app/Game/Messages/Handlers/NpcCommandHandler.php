<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\Character;
use App\Flare\Values\ItemEffectsValue;
use Exception;
use Illuminate\Broadcasting\PendingBroadcast;
use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcComponentsValue;
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

    private $npcQuestsHandler;

    /**
     * KINGDOM_COST
     */
    private const KINGDOM_COST = 10000;

    /**
     * NpcCommandHandler constructor.
     *
     * @param NpcServerMessageBuilder $npcServerMessageBuilder
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @param Manager $manager
     */
    public function __construct(
        NpcServerMessageBuilder $npcServerMessageBuilder,
        NpcQuestsHandler        $npcQuestsHandler,
    ) {
        $this->npcServerMessageBuilder    = $npcServerMessageBuilder;
        $this->npcQuestHandler            = $npcQuestsHandler;
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
            if (!$character->map->gameMap->mapType()->isHell()) {
                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('queen_plane', $npc), true));
            }

            if (!$this->characterHasQuestItemToIntereact($character, ItemEffectsValue::QUEEN_OF_HEARTS)) {
                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('missing_queen_item', $npc), true));
            } else {
                broadcast(new NpcComponentShowEvent($user, NpcComponentsValue::ENCHANT));

                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('what_do_you_want', $npc), true));
            }


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

    protected function characterHasQuestItemToIntereact(Character $character, string $type): bool {
        $foundQuestItem = $character->inventory->slots->filter(function($slot) use($type) {
            return $slot->item->type === 'quest' && $slot->item->effect === $type;
        })->first();

        return !is_null($foundQuestItem);
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

    protected function handleQuest($user, $npc): bool {
        return $this->npcQuestHandler->handleNpcQuests($user->character, $npc);
    }
}
