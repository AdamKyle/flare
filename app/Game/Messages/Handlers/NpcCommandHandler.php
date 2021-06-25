<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcComponentsValue;
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

        if ($type->isTakeKingdom()) {
            if ($this->handleTakingKingdom($user, $npc->name)) {
                $message     = $user->character->name . ' Has paid The Old Man for a kingdom on the ' . $user->character->map->gameMap->name . ' plane.';
                $messageType = 'took_kingdom';
            }
        }

        if ($type->isConjure()) {
            if ($npc->must_be_at_same_location) {
                $character = $user->character;

                if ($character->x_position !== $npc->x_position && $character->y_position !== $npc->y_position) {
                    return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('location', $npc), true));
                }
            }

            broadcast(new NpcComponentShowEvent($user, NpcComponentsValue::CONJURE));

            return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('take_a_look', $npc), true));
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
}
