<?php

namespace App\Game\Exploration\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Services\ProcessExplorationFightService;

class ExplorationHandler {

    private $descriptions = [
        'The bank of a raging river in the Ynel Hills. The air is filled with a metallic odor.',
        'The overgrown ruins of a stone tower in the Mistfen Marsh. The shadows seem to be alive, and move in the darkness.',
        'A thicket of thorns amidst a decaying swamp lays ahead of you. Strange sounds can be heard from the swamp.',
        'A starlit clearing amidst a forest of flowering trees. The sky is filled with howling winds and flashes of lightning.',
        'Deep in the Kalitarie Jungle. A natural stone bridge crosses a murky river nearby.',
        'Somewhere amidst broken hills. A company of grim mercenaries rests in their camp. You move cautiously.',
        'I came across a small church. Quickly did I notice it has no doors or windows.',
        'An outcrop of wind-carved rock in the Pale Waste. The air is strangely still and quiet.',
        'A shambling mound of vines lurks in the undergrowth.',
    ];

    private $processExplorationFightService;

    public function __construct(ProcessExplorationFightService $processExplorationFightService) {
        $this->processExplorationFightService = $processExplorationFightService;
    }


    public function explore(Character $character, CharacterAutomation $characterAutomation) {
        event(new ExplorationLogUpdate($character->user, 'You jot down in your log: ' . $this->fetchRandomDescription(), true));

        $this->processExplorationFightService->processFight($characterAutomation, $character);

        event(new ExplorationLogUpdate($character->user, 'Child what did I tell you about paying attention to your surroundings, you could have died. Wait... Where are you going? This is no time to hunt them down! Christ, we\'re all going to die.', true));
    }

    protected function fetchRandomDescription(): String {
        return $this->descriptions[rand(0, count($this->descriptions) - 1)];
    }
}
