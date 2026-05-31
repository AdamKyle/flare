<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Requests\FactionLoyaltyAutomationWarningRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyaltyAutomationWarning;

class FactionLoyaltyAutomationWarningControllerTest extends TestCase
{
    use CreateFactionLoyaltyAutomationWarning, RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomationLog $factionLoyaltyAutomationLog = null;

    public function setUp(): void
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
    }

    public function tearDown(): void
    {
        $this->character = null;
        $this->factionLoyaltyFactory = null;
        $this->factionLoyaltyNpc = null;
        $this->factionLoyaltyAutomationLog = null;

        parent::tearDown();
    }

    public function testDismissWarningUsesRequestAndDeletesLatestWarningWithReferencedLogEntry(): void
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

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
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

    public function testDismissWarningRequiresAuthentication(): void
    {
        $response = $this->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/warning/dismiss', [
            '_token' => csrf_token(),
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testWarningRequestAuthorizesAndHasNoValidationRules(): void
    {
        $request = new FactionLoyaltyAutomationWarningRequest();

        $this->assertTrue($request->authorize());
        $this->assertEquals([], $request->rules());
    }
}
