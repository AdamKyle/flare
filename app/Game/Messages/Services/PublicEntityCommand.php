<?php

namespace App\Game\Messages\Services;


use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Support\Facades\Log;

class PublicEntityCommand {

    /**
     * @var Character|null $character
     */
    private ?Character $character;

    /**
     * @var PctService $pctService
     */
    private PctService $pctService;

    /**
     * @param PctService $pctService
     */
    public function __construct(PctService $pctService) {
        $this->pctService = $pctService;
    }

    /**
     * Set the character from the user object.
     *
     * @param User $user
     * @return $this
     */
    public function setCharacter(User $user): PublicEntityCommand {
        $this->character = $user->character;

        return $this;
    }

    /**
     * use /pc command.
     *
     * Will show the user the location of the Celestial entity if there is any
     * or alert the user to the fact that there are no celestial entities.
     *
     * If the character is null, we simply return.
     *
     * @return void
     * @see PctService
     */
    public function usPCCommand(): void {

        if (is_null($this->character)) {
            return;
        }

        $success = $this->pctService->usePCT($this->character);

        if (!$success) {
            event(new ServerMessageEvent($this->character->user, 'There are no celestials in the world right now, child!'));
        }
    }

    /**
     * Use the /pct command.
     *
     *  - If the character is null, we simply bail.
     *  - If the check for thw quest item fails, we alert the player and log and error with the exception.
     *  - If we fail to find a celestial, we simply state there are none.
     *
     * @return void
     * @see PctService
     */
    public function usePCTCommand(): void {
        if (is_null($this->character)) {
            return;
        }

        try {
            if (!$this->hasQuestItemForPCT()) {
                broadcast(new ServerMessageEvent($this->character->user, 'You are missing a quest item to do that.'));

                return;
            }

            $success = $this->pctService->usePCT($this->character, true);

            if (!$success) {
                event(new ServerMessageEvent($this->character->user, 'There are no celestials in the world right now, child!'));
            }

            return;
        } catch (Exception $e) {
            new ServerMessage('Christ child! Something went wrong. Alert The Creator (probs best to head to discor and post in #bugs section. Hover over rpfile icon, click: Discord to join). /pct is not working!');

            Log::error($e->getMessage());

            return;
        }
    }

    /**
     * Is the characters automation running?
     *
     * @return bool
     */
    protected function isAutomationRunning(): bool {

        if ($this->character->currentAutomations()->isEmpty()) {
            return false;
        }

        event(new ServerMessageEvent($this->character->user, 'You are to preoccupied to do this. (You cannot be Exploring).'));

        return true;
    }

    /**
     * Can character use the pct command?
     *
     * - Must be able to move
     * - Cannot be dead
     *
     * @return bool
     */
    protected function isCharacterAbleToUsePCT(): bool {

        if (!$this->character->can_move || $this->character->is_dead) {
            event(new ServerMessageEvent($this->character->user, 'You are to preoccupied to do this. (You must be able to move and cannot be dead).'));

            return false;
        }

        return true;
    }

    /**
     * Do we have the quest item for the /pct command?
     *
     * - Can throw an exception if the items effect type is invalid.
     *
     * @return bool
     * @throws Exception
     */
    protected function hasQuestItemForPCT(): bool {
        return $this->character->inventory->slots->filter(function ($slot) {
            if ($slot->item->type === 'quest' && !is_null($slot->item->effect)) {
                return (new ItemEffectsValue($slot->item->effect))->teleportToCelestial();
            }
        })->isNotEmpty();
    }


}
