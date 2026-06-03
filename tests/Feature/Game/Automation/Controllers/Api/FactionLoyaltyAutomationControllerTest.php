<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameMap;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationControllerTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character);

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
    }

    public function tearDown(): void
    {
        $this->character = null;
        $this->factionLoyaltyFactory = null;
        $this->factionLoyaltyNpc = null;

        parent::tearDown();
    }

    public function testBeginStartsFactionLoyaltyAutomationSuccessfully(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'You have now begun automation to help out: ' . $this->factionLoyaltyNpc->npc->real_name . ' This will automatically end in 8 hours. You can manually end it at any time. Crafting has been disabled while faction loyalty automation is running. Keep an eye on the Automation tab to see your progress.',
            $jsonData['message']
        );
        $this->assertEquals($this->character->id, $factionLoyaltyAutomation->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $factionLoyaltyAutomation->faction_loyalty_npc_id);
    }

    public function testBeginReturns422ForInvalidAttackType(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => 'invalid',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid attack type was selected. Please select from the drop down.', $jsonData['message']);
    }

    public function testBeginReturnsValidationErrorWhenAttackTypeIsMissing(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid input.', $jsonData['message']);
    }

    public function testBeginReturns422WhenAnotherAutomationIsAlreadyRunning(): void
    {
        Queue::fake();
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Faction Loyalty automation is running. Cancel it first.', $jsonData['message']);
    }

    public function testBeginReturns422WhenExplorationIsRunning(): void
    {
        Queue::fake();
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Exploration. This action cannot be completed right now. Please cancel Exploration first.', $jsonData['message']);
        $this->assertNull(FactionLoyaltyAutomation::query()->latest('id')->first());
    }

    public function testBeginReturns422WhenDelveIsRunning(): void
    {
        Queue::fake();
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Delve. This action cannot be completed right now. Please cancel Delve first.', $jsonData['message']);
        $this->assertNull(FactionLoyaltyAutomation::query()->latest('id')->first());
    }

    public function testBeginReturns422WhenCharacterIsNotPledgedToAFaction(): void
    {
        Queue::fake();
        Event::fake();

        $this->character->factionLoyalties()->update([
            'is_pledged' => false,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You must be pledged to a faction before automating faction loyalty.', $jsonData['message']);
    }

    public function testBeginReturns422WhenCharacterIsNotAssistingAnNpc(): void
    {
        Queue::fake();
        Event::fake();

        $this->factionLoyaltyNpc->factionLoyalty->factionLoyaltyNpcs()->update([
            'currently_helping' => false,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You must be assisting an NPC before automating faction loyalty.', $jsonData['message']);
    }

    public function testBeginReturns422WhenCharacterIsNotOnTheNpcMap(): void
    {
        Queue::fake();
        Event::fake();

        $gameMap = GameMap::factory()->create([
            'name' => 'Other Map',
            'path' => 'other-map',
            'default' => false,
        ]);

        $this->character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You must be on the same map as the NPC you are assisting.', $jsonData['message']);
    }

    public function testBeginReturns422WhenNpcHasNoIncompleteTasks(): void
    {
        Queue::fake();
        Event::fake();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

        foreach ($fameTasks as $index => $fameTask) {
            $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
        }

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $fameTasks,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('This NPC does not have any incomplete tasks for you to automate.', $jsonData['message']);
    }

    public function testStopStopsFactionLoyaltyAutomationSuccessfully(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/stop', [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $jsonData);
        $this->assertEquals(0, $this->character->currentAutomations()->count());
    }

    public function testStopReturnsControllerServiceErrorResponseWhenNoFactionLoyaltyAutomationExists(): void
    {
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/stop', [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Nope. You don\'t own that.', $jsonData['message']);
    }

    public function testMarkWarningNoticeReadUpdatesLatestUnreadNotice(): void
    {
        Event::fake();

        $characterAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $characterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
        ]);
        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'fight_logs' => [
                [
                    'log_entry_id' => 'matching-log-entry',
                    'outcome' => 'warning_outcome',
                    'monster_id' => 10,
                ],
                [
                    'log_entry_id' => 'unrelated-log-entry',
                    'outcome' => 'unrelated_outcome',
                    'monster_id' => 20,
                ],
            ],
        ]);
        $warning = FactionLoyaltyAutomationWarning::create([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'faction_loyalty_automation_log_id' => $factionLoyaltyAutomationLog->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'matching-log-entry',
            'type' => 'bounty_stalled_max_attempts_reached',
            'message' => 'Warning message.',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $this->character->id . '/warning/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($warning->fresh());
        $this->assertEquals([
            [
                'log_entry_id' => 'unrelated-log-entry',
                'outcome' => 'unrelated_outcome',
                'monster_id' => 20,
            ],
        ], $factionLoyaltyAutomationLog->refresh()->fight_logs);
    }
}
