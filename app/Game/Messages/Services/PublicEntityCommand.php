<?php

namespace App\Game\Messages\Services;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Support\Facades\Log;

class PublicEntityCommand
{
    private ?Character $character = null;

    private PctService $pctService;

    public function __construct(PctService $pctService)
    {
        $this->pctService = $pctService;
    }

    /**
     * Set the character from the user object.
     *
     * @return $this
     */
    public function setCharacter(User $user): PublicEntityCommand
    {
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
     * @see PctService
     */
    public function usPCCommand(): void
    {

        if (is_null($this->character)) {
            return;
        }

        $success = $this->pctService->usePCT($this->character);

        if (! $success) {
            event(new ServerMessageEvent($this->character->user, 'There are no celestials in the world right now, child!'));
        }
    }

    /**
     * Use the /pct command.
     *
     *  - If the character is null, we simply bail.
     *  - If the check for the quest item fails, we alert the player and log and error with the exception.
     *  - If we fail to find a celestial, we simply state there are none.
     *
     * @see PctService
     */
    public function usePCTCommand(): void
    {
        if (is_null($this->character)) {
            return;
        }

        try {
            if (! $this->hasQuestItemForPCT()) {
                broadcast(new ServerMessageEvent($this->character->user, 'You are missing a quest item to use /PCT. You need to complete the Quest: Hunting Expedition on Surface.'));

                return;
            }

            $success = $this->pctService->usePCT($this->character, true);

            if (! $success) {
                event(new ServerMessageEvent($this->character->user, 'There are no celestials in the world right now, child!'));
            }

            return;
        } catch (Exception $e) {
            event(new ServerMessageEvent($this->character->user, 'Christ child! Something went wrong. Alert The Creator (probs best to head to discord and post in #bugs section. Hover over profile icon, click: Discord to join). /pct is not working!'));

            Log::error($e->getMessage());

            return;
        }
    }

    /**
     * Do we have the quest item for the /pct command?
     *
     * - Can throw an exception if the items effect type is invalid.
     *
     * @throws Exception
     */
    protected function hasQuestItemForPCT(): bool
    {
        return $this->character->inventory->slots->filter(function ($slot) {
            if ($slot->item->type === 'quest' && ! is_null($slot->item->effect)) {
                return (new ItemEffectsValue($slot->item->effect))->teleportToCelestial();
            }
        })->isNotEmpty();
    }
}
