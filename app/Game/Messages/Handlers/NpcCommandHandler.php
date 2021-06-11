<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Flare\Values\NpcCommandTypes;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class NpcCommandHandler {

    private $npcServerMessageBuilder;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    public function handleForType(int $type, string $npcName, User $user) {
        $type = new NpcCommandTypes($type);

        if ($type->isTakeKingdom()) {
            if ($this->handleTakingKingdom($user, $npcName)) {
                broadcast(new GlobalMessageEvent($user->character->name . ' Has paid The Old Man for a kingdom on the ' . $user->character->map->gameMap->name . ' plane.'));

                return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('took_kingdom', $npcName), true));
            }
        }
    }

    protected function handleTakingKingdom(User $user, string $npcName) {
        $character      = $user->character;
        $characterX     = $character->map->x_position;
        $characterY     = $character->map->y_position;
        $characterMapId = $character->map->game_map_id;
        $tookKingdom    = false;

        $kingdom = Kingdom::where('character_id', '!=', $character->id)
                          ->where('x_position', $characterX)
                          ->where('y_position', $characterY)
                          ->where('game_map_id', $characterMapId)
                          ->first();

        if (is_null($kingdom)) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('cannot_have', $npcName), true));
        }

        return $tookKingdom;
    }
}
