<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Services\FactionLoyaltyAutomationWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyaltyAutomationWarning;

class FactionLoyaltyAutomationWarningServiceTest extends TestCase
{
    use CreateFactionLoyaltyAutomationWarning, RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomationLog $factionLoyaltyAutomationLog = null;

    private ?FactionLoyaltyAutomationWarningService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomationLog = $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog();
        $this->service = resolve(FactionLoyaltyAutomationWarningService::class);
    }

    protected function tearDown(): void
    {
        $this->character = null;
        $this->factionLoyaltyFactory = null;
        $this->factionLoyaltyNpc = null;
        $this->factionLoyaltyAutomationLog = null;
        $this->service = null;

        parent::tearDown();
    }

    public function test_dismiss_latest_warning_deletes_warning_and_referenced_log_entry(): void
    {
        $this->factionLoyaltyAutomationLog->update([
            'fight_logs' => [
                [
                    'log_entry_id' => 'older-log-entry',
                    'outcome' => 'older_warning',
                    'monster_id' => 10,
                ],
                [
                    'log_entry_id' => 'latest-log-entry',
                    'outcome' => 'latest_warning',
                    'monster_id' => 20,
                ],
                [
                    'log_entry_id' => 'unrelated-log-entry',
                    'outcome' => 'unrelated_warning',
                    'monster_id' => 30,
                ],
            ],
        ]);

        $olderWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyFactory->getFactionLoyaltyAutomation()->id,
            'faction_loyalty_automation_log_id' => $this->factionLoyaltyAutomationLog->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'older-log-entry',
            'type' => 'bounty',
            'message' => 'Older warning message.',
        ]);
        $latestWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyFactory->getFactionLoyaltyAutomation()->id,
            'faction_loyalty_automation_log_id' => $this->factionLoyaltyAutomationLog->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'latest-log-entry',
            'type' => 'crafting',
            'message' => 'Latest warning message.',
        ]);

        $this->service->dismissLatestWarning($this->character);

        $this->assertNotNull($olderWarning->refresh());
        $this->assertNull($latestWarning->fresh());
        $this->assertEquals(1, FactionLoyaltyAutomationWarning::where('character_id', $this->character->id)->count());
        $this->assertEquals([
            [
                'log_entry_id' => 'older-log-entry',
                'outcome' => 'older_warning',
                'monster_id' => 10,
            ],
            [
                'log_entry_id' => 'unrelated-log-entry',
                'outcome' => 'unrelated_warning',
                'monster_id' => 30,
            ],
        ], $this->factionLoyaltyAutomationLog->refresh()->fight_logs);
    }

    public function test_dismiss_latest_warning_does_nothing_when_no_warning_exists(): void
    {
        $this->service->dismissLatestWarning($this->character);

        $this->assertEquals(0, FactionLoyaltyAutomationWarning::where('character_id', $this->character->id)->count());
    }
}
