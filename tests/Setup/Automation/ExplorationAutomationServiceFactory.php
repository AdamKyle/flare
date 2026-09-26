<?php

namespace Tests\Setup\Automation;

use App\Admin\Services\MonitoredBugReportService;
use App\Game\Automation\Exploration\Services\ExplorationAutomationService;
use App\Game\Automation\Exploration\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\Automation\Exploration\Services\ExplorationWarningService;
use App\Game\Automation\Services\AutomationRestrictionService;
use Tests\Setup\Character\CharacterCacheDataFactory;

class ExplorationAutomationServiceFactory
{
    /**
     * Build a real ExplorationAutomationService instance with real collaborators for tests.
     *
     * @param ?ExplorationLogService $explorationLogService
     */
    public function build(?ExplorationLogService $explorationLogService = null): ExplorationAutomationService
    {
        $characterCacheDataFactory = new CharacterCacheDataFactory;

        return new ExplorationAutomationService(
            $characterCacheDataFactory->build(),
            new ExplorationCreatureCountCalculator($characterCacheDataFactory->buildCharacterStatBuilder()),
            $explorationLogService ?? new ExplorationLogService(),
            new ExplorationWarningService(),
            new AutomationRestrictionService(),
            new MonitoredBugReportService(),
        );
    }
}
