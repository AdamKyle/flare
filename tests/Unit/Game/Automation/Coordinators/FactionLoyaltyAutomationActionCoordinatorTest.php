<?php

namespace Tests\Unit\Game\Automation\Coordinators;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationActionCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private ?FactionLoyaltyAutomationActionCoordinator $coordinator = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->coordinator = resolve(FactionLoyaltyAutomationActionCoordinator::class);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();
    }

    public function tearDown(): void
    {
        $this->coordinator = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyNpc = null;
        $this->factionLoyaltyAutomation = null;

        parent::tearDown();
    }

    public function testResolveActionReturnsNullWhenNpcHasNoTaskRecord(): void
    {
        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->delete();

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertNull($result);
    }

    public function testResolveActionReturnsNullWhenAllTasksAreComplete(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

        foreach ($fameTasks as $index => $fameTask) {
            $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
        }

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $fameTasks,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertNull($result);
    }

    public function testResolveActionPrioritizesFailedCraftingTask(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $craftingTask = collect($fameTasks)->first(function (array $fameTask): bool {
            return isset($fameTask['item_id']);
        });

        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => $craftingTask['item_id'],
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
        $this->assertEquals($craftingTask['item_id'], $result['task']['item_id']);
    }

    public function testResolveActionPrioritizesFailedCraftingTaskBeforeFailedBountyTask(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $craftingTask = collect($fameTasks)->first(function (array $fameTask): bool {
            return isset($fameTask['item_id']);
        });
        $bountyTask = collect($fameTasks)->first(function (array $fameTask): bool {
            return ($fameTask['type'] ?? null) === 'bounty';
        });

        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => $craftingTask['item_id'],
            'failed_bounty_monster_id' => $bountyTask['monster_id'],
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
        $this->assertEquals($craftingTask['item_id'], $result['task']['item_id']);
    }

    public function testResolveActionPrioritizesFailedBountyTask(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $bountyTask = collect($fameTasks)->first(function (array $fameTask): bool {
            return ($fameTask['type'] ?? null) === 'bounty';
        });

        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyTask['monster_id'],
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::FIGHT->value, $result['type']);
        $this->assertEquals($bountyTask['monster_id'], $result['task']['monster_id']);
    }

    public function testResolveActionFallsBackWhenFailedCraftingTaskIsNotIncomplete(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => 999999,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionFallsBackWhenFailedBountyTaskIsNotIncomplete(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => 999999,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsCraftWhenBothTaskTypesExistAndNoLogExists(): void
    {
        $this->factionLoyaltyAutomation->log()->delete();

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsCraftWhenBothTaskTypesExistAndLogsAreEmpty(): void
    {
        $this->factionLoyaltyAutomation->log()->update([
            'crafting_logs' => [],
            'fight_logs' => [],
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsFightWhenLastActionWasCrafting(): void
    {
        $this->factionLoyaltyAutomation->update([
            'last_automation_action' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'last_automation_action_at' => now(),
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::FIGHT->value, $result['type']);
    }

    public function testResolveActionReturnsCraftWhenLastActionWasFighting(): void
    {
        $this->factionLoyaltyAutomation->update([
            'last_automation_action' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'last_automation_action_at' => now(),
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsFightWhenCompactStateLastActionIsCraft(): void
    {
        $this->factionLoyaltyAutomation->update([
            'last_automation_action' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'last_automation_action_at' => now(),
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::FIGHT->value, $result['type']);
    }

    public function testResolveActionReturnsCraftWhenCompactStateLastActionIsFight(): void
    {
        $this->factionLoyaltyAutomation->update([
            'last_automation_action' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'last_automation_action_at' => now(),
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsCraftWhenOnlyCraftingTasksExist(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $craftingTasks = collect($fameTasks)->filter(function (array $fameTask): bool {
            return isset($fameTask['item_id']);
        })->values()->toArray();

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $craftingTasks,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function testResolveActionReturnsFightWhenOnlyBountyTasksExist(): void
    {
        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $bountyTasks = collect($fameTasks)->filter(function (array $fameTask): bool {
            return ($fameTask['type'] ?? null) === 'bounty';
        })->values()->toArray();

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $bountyTasks,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::FIGHT->value, $result['type']);
    }
}
