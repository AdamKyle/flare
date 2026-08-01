<?php

namespace Tests\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use Illuminate\Http\Request;

trait CreateAutomationRequest
{
    public function createAutomationJsonRequest(): Request
    {
        return Request::create('/test', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    public function createAutomationWebRequest(): Request
    {
        return Request::create('/test', 'POST', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
    }

    public function createExplorationAutomation(Character $character): CharacterAutomation
    {
        return $this->createCharacterAutomationRecord([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
        ]);
    }
}
