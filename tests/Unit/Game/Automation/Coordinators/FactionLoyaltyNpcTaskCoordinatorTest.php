<?php

namespace Tests\Unit\Game\Automation\Coordinators;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Session;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\TraverseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyNpcTaskCoordinatorTest extends TestCase
{
    use MockeryPHPUnitIntegration, RefreshDatabase;

    private ?FactionLoyaltyNpcTaskCoordinator $coordinator = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        $this->coordinator = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyNpc = null;
        $this->factionLoyaltyAutomation = null;

        parent::tearDown();
    }

    public function test_resolve_npc_returns_current_npc_when_it_has_incomplete_tasks(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 1, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertSame($this->factionLoyaltyNpc->id, $result->id);
    }

    public function test_resolve_npc_switches_to_same_map_npc_with_incomplete_tasks_when_current_is_complete(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 1, 3)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNotNull($result);
        $this->assertNotSame($this->factionLoyaltyNpc->id, $result->id);
        $this->assertSame($result->id, $this->factionLoyaltyAutomation->fresh()->faction_loyalty_npc_id);
    }

    public function test_resolve_npc_ends_automation_when_no_incomplete_tasks_exist_anywhere(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 1, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNull($result);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_travels_pledges_and_assists_existing_faction_npc_with_incomplete_tasks(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $otherFactionLoyaltyNpc = collect($this->factionLoyaltyFactory->getFactionLoyaltyNpcs())
            ->first(fn (FactionLoyaltyNpc $npc): bool => $npc->id !== $this->factionLoyaltyNpc->id);

        $otherFaction = $otherFactionLoyaltyNpc->factionLoyalty->faction;

        $this->instance(
            TraverseService::class,
            Mockery::mock(TraverseService::class, function (MockInterface $mock) {
                $mock->shouldReceive('canTravel')->andReturn(true);
            })
        );

        $this->instance(
            MovementService::class,
            Mockery::mock(MovementService::class, function (MockInterface $mock) use ($otherFaction) {
                $mock->shouldReceive('updateCharacterPlane')
                    ->with($otherFaction->game_map_id, Mockery::type(Character::class))
                    ->andReturn(['status' => 200]);
            })
        );

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNotNull($result);
        $this->assertSame($otherFactionLoyaltyNpc->id, $result->id);
        $this->assertTrue($otherFactionLoyaltyNpc->factionLoyalty->fresh()->is_pledged);
    }

    public function test_resolve_npc_skips_existing_faction_when_travel_is_not_possible(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $this->instance(
            TraverseService::class,
            Mockery::mock(TraverseService::class, function (MockInterface $mock) {
                $mock->shouldReceive('canTravel')->andReturn(false);
            })
        );

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNull($result);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_skips_existing_faction_when_travel_fails_mid_pledge(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $this->instance(
            TraverseService::class,
            Mockery::mock(TraverseService::class, function (MockInterface $mock) {
                $mock->shouldReceive('canTravel')->andReturn(true);
            })
        );

        $this->instance(
            MovementService::class,
            Mockery::mock(MovementService::class, function (MockInterface $mock) {
                $mock->shouldReceive('updateCharacterPlane')->andReturn(['status' => 422]);
            })
        );

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNull($result);
        $this->assertFalse($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_skips_existing_faction_when_pledge_fails(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $this->instance(
            TraverseService::class,
            Mockery::mock(TraverseService::class, function (MockInterface $mock) {
                $mock->shouldReceive('canTravel')->andReturn(true);
            })
        );

        $this->instance(
            MovementService::class,
            Mockery::mock(MovementService::class, function (MockInterface $mock) {
                $mock->shouldReceive('updateCharacterPlane')->andReturn(['status' => 200]);
            })
        );

        $this->instance(
            FactionLoyaltyService::class,
            Mockery::mock(FactionLoyaltyService::class, function (MockInterface $mock) {
                $mock->shouldReceive('pledgeLoyalty')->andReturn(['status' => 422, 'message' => 'Nope.']);
            })
        );

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNull($result);
        $this->assertFalse($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_skips_existing_unmaxed_faction(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $otherFactionLoyaltyNpc = collect($this->factionLoyaltyFactory->getFactionLoyaltyNpcs())
            ->first(fn (FactionLoyaltyNpc $npc): bool => $npc->id !== $this->factionLoyaltyNpc->id);

        $otherFactionLoyaltyNpc->factionLoyalty->faction->update(['maxed' => false]);

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $result = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertNull($result);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_broadcasts_log_update_when_character_is_logged_in(): void
    {
        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 1, 1)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $task = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;
        $completedTasks = array_map(
            static fn (array $fameTask): array => array_merge($fameTask, ['current_amount' => $fameTask['required_amount']]),
            $task->fame_tasks
        );
        $task->update(['fame_tasks' => $completedTasks]);

        $session = new Session;
        $session->timestamps = false;
        $session->forceFill([
            'id' => Str::random(40),
            'user_id' => $this->character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->timestamp,
        ])->save();

        Event::fake();

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation)
            ->resolveNpc();

        Event::assertDispatched(AutomationLogUpdate::class);
    }
}
