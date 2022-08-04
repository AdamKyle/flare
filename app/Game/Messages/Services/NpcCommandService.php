<?php

namespace App\Game\Messages\Services;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Npc;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcComponentsValue;
use App\Flare\Values\NpcTypes;
use App\Flare\Values\AutomationType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Jobs\ProcessNPCCommands;

class NpcCommandService {


    private $npcServerMessageBuilder;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    public function handleNPC(Character $character, Npc $npc, string $message) {
        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty()) {
            broadcast(new ServerMessageEvent($character->user, 'Child, listen! You are so busy thrashing about that you can\'t even focus on this conversation. Stop the auto fighting and then talk to me. Got it? Clear enough? Christ, child!', true));

            return;
        }

        $command = $npc->commands->where('command', $message)->first();

        if (!is_null($command)) {

            broadcast(new ServerMessageEvent($character->user, 'Processing message...'));

            $this->handleForType($character, $npc);

            return;
        }

        event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('no_matching_command', $npc), true));
    }

    public function handleForType(Character $character, Npc $npc) {
        $type = new NpcTypes($npc->type);

        if (!$this->canInteract($character, $npc)) {
            return;
        }

        if ($type->isConjurer()) {
            event(new NpcComponentShowEvent($character->user, NpcComponentsValue::CONJURE));

            return event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('take_a_look', $npc), true));
        }

        if ($type->isEnchantress()) {
            return $this->handleEnchantress($character, $npc);
        }

        if ($type->isQuestHolder()) {
            ProcessNPCCommands::dispatch($character->user, $npc, NpcCommandTypes::QUEST);
        }
    }

    protected function handleEnchantress(Character $character, Npc $npc) {
        if (!$character->map->gameMap->mapType()->isHell()) {
            return broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('queen_plane', $npc), true));
        }

        if (!$this->characterHasQuestItemToInteract($character, ItemEffectsValue::QUEEN_OF_HEARTS)) {
            return broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('missing_queen_item', $npc), true));
        } else {
            broadcast(new NpcComponentShowEvent($character->user, NpcComponentsValue::ENCHANT));

            return broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('what_do_you_want', $npc), true));
        }
    }


    protected function characterHasQuestItemToInteract(Character $character, string $type): bool {
        $foundQuestItem = $character->inventory->slots->filter(function($slot) use($type) {
            return $slot->item->type === 'quest' && $slot->item->effect === $type;
        })->first();

        return !is_null($foundQuestItem);
    }

    protected function canInteract(Character $character, Npc $npc): bool {
        if ($character->is_dead) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('dead', $npc), true));

            return false;
        }

        if (!$character->can_adventure) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('adventuring', $npc), true));

            return false;
        }

        if ($npc->must_be_at_same_location) {
            if ($character->x_position !== $npc->x_position && $character->y_position !== $npc->y_position) {
                broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('location', $npc), true));

                return false;
            }
        }

        return true;
    }
}
