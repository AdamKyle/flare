<?php

namespace Tests\Setup\Automation;

use App\Game\Automation\Delve\Services\DelveExplorationAutomationService;
use Tests\Setup\Character\CharacterCacheDataFactory;

class DelveExplorationAutomationServiceFactory
{
    /**
     * Build a real DelveExplorationAutomationService instance with real collaborators for tests.
     */
    public function build(): DelveExplorationAutomationService
    {
        return new DelveExplorationAutomationService((new CharacterCacheDataFactory)->build());
    }
}
