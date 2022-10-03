<?php

namespace App\Game\Messages\Handlers;

use Exception;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class NpcCommandHandler {

    use KingdomCache;

    /**
     * @var NpcServerMessageBuilder $npcServerMessageBuilder
     */
    private NpcServerMessageBuilder $npcServerMessageBuilder;

    /**
     * KINGDOM_COST
     */
    private const KINGDOM_COST = 10000;

    /**
     * NpcCommandHandler constructor.
     *
     * @param NpcServerMessageBuilder $npcServerMessageBuilder
     */
    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder,) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    /**
     * Handle the command.
     *
     * @param int $type
     * @param Npc $npc
     * @param User $user
     * @throws Exception
     */
    public function handleForType(int $type, Npc $npc, User $user) {
        $message     = null;
        $messageType = null;

        if ($user->character->is_dead) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('dead', $npc), true));

            return;
        }

        if (!is_null($message) && !is_null($messageType)) {
            broadcast(new GlobalMessageEvent($message));

            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build($messageType, $npc), true));

            return;
        }

        if (!is_null($messageType)) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build($messageType, $npc), true));
        }
    }
}
