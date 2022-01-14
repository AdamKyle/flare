<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\PassiveSkill;
use App\Flare\Values\ItemEffectsValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
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
        NpcKingdomHandler       $npcKingdomHandler,
    ) {
        $this->npcServerMessageBuilder    = $npcServerMessageBuilder;
        $this->npcQuestHandler            = $npcQuestsHandler;
        $this->npcKingdomHandler          = $npcKingdomHandler;
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
        $type        = new NpcCommandTypes($type);
        $message     = null;
        $messageType = null;

        if ($user->character->is_dead) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('dead', $npc), true));

            return;
        }

        if (!$user->character->can_adventure) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('adventuring', $npc), true));

            return;
        }

        if ($npc->must_be_at_same_location) {
            $character = $user->character;

            if ($character->x_position !== $npc->x_position && $character->y_position !== $npc->y_position) {
                broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('location', $npc), true));

                return;
            }
        }

        if ($type->isQuest()) {
            if ($this->handleQuest($user, $npc)) {
                $message     = $user->character->name . ' has completed a quest for: ' . $npc->real_name . ' and has been rewarded with a godly gift!';
                $messageType = 'quest_complete';
            } else {
                $messageType = 'no_quests';
            }
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

    protected function handleQuest($user, $npc): bool {
        return $this->npcQuestHandler->handleNpcQuests($user->character, $npc);
    }
}
