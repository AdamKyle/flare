<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Game\Quests\Traits\QuestDetails;
use App\Game\Quests\Handlers\NpcQuestsHandler;

class QuestHandlerService {

    use QuestDetails;

    /**
     * @var string $bailMessage
     */
    private string $bailMessage = '';

    /**
     * @var NpcQuestsHandler $npcQuestsHandler
     */
    private NpcQuestsHandler $npcQuestsHandler;

    /**
     * @param NpcQuestsHandler $npcQuestsHandler
     */
    public function __construct(NpcQuestsHandler $npcQuestsHandler) {
        $this->npcQuestsHandler = $npcQuestsHandler;
    }

    /**
     * Fetch the npc quest handler instance.
     *
     * @return NpcQuestsHandler
     */
    public function npcQuestsHandler(): NpcQuestsHandler {
        return $this->npcQuestsHandler;
    }

    /**
     * Should we bail on the quest?
     *
     * @param Character $character
     * @param Quest $quest
     * @return bool
     */
    public function shouldBailOnQuest(Character $character, Quest $quest): bool {
        $completedQuests = $character->questsCompleted->pluck('quest_id')->toArray();

        if (!$this->validateParentQuest($quest, $completedQuests)) {
            $this->bailMessage = 'You must finish the parent quest first ...';

            return true;
        }

        if ($this->questRequiresItem($quest)) {
            $foundItem = $this->fetchRequiredItem($quest, $character);

            if (is_null($foundItem)) {
                $this->bailMessage = 'You are missing a required item. Check the requirements tab.';

                return true;
            }
        }

        if ($this->questRequiresSecondaryItem($quest)) {
            $secondaryItem = $this->fetchSecondaryRequiredItem($quest, $character);

            if (is_null($secondaryItem)) {
                $this->bailMessage = 'You are missing a secondary required item. Check the requirements tab.';

                return true;
            }
        }

        if ($this->questHasCurrenciesRequirement($quest)) {
            if (!$this->canPay($character, $quest)) {
                $this->bailMessage = 'You don\'t have the currencies required. Check the requirements tab.';

                return true;
            }
        }

        if ($this->questRequiresPlaneAccess($quest)) {
            if (!$this->hasPlaneAccess($quest, $character)) {
                $this->bailMessage = 'You do not have proper plane access to finish this quest. Check the requirements tab.';

                return true;
            }
        }

        if ($this->questHasFactionRequirement($quest)) {
            if (!$this->hasMetFactionRequirement($character, $quest)) {
                $this->bailMessage = 'You are missing the required Faction Level needed to complete this quest. Check the requirements tab.';

                return true;
            }
        }

        return false;
    }

    /**
     * get the bail reason.
     *
     * @return string
     */
    public function getBailMessage(): string {
        return $this->bailMessage;
    }
}
