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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        $this->coordinator = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyNpc = null;
        $this->factionLoyaltyAutomation = null;

        parent::tearDown();
    }

    public function test_resolve_action_returns_null_when_npc_has_no_task_record(): void
    {
        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->delete();

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertNull($result);
    }

    public function test_resolve_action_returns_null_when_all_tasks_are_complete(): void
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

    public function test_resolve_action_prioritizes_failed_crafting_task(): void
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

    public function test_resolve_action_prioritizes_failed_crafting_task_before_failed_bounty_task(): void
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

    public function test_resolve_action_prioritizes_failed_bounty_task(): void
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

    public function test_resolve_action_falls_back_when_failed_crafting_task_is_not_incomplete(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => 999999,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function test_resolve_action_falls_back_when_failed_bounty_task_is_not_incomplete(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => 999999,
        ]);

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function test_resolve_action_returns_craft_when_both_task_types_exist_and_no_log_exists(): void
    {
        $this->factionLoyaltyAutomation->log()->delete();

        $result = $this->coordinator
            ->setUp($this->factionLoyaltyAutomation->refresh(), $this->factionLoyaltyNpc->refresh())
            ->resolveAction();

        $this->assertEquals(FactionLoyaltyCoordinatorAction::CRAFT->value, $result['type']);
    }

    public function test_resolve_action_returns_craft_when_both_task_types_exist_and_logs_are_empty(): void
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

    public function test_resolve_action_returns_fight_when_last_action_was_crafting(): void
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

    public function test_resolve_action_returns_craft_when_last_action_was_fighting(): void
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

    public function test_resolve_action_returns_fight_when_compact_state_last_action_is_craft(): void
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

    public function test_resolve_action_returns_craft_when_compact_state_last_action_is_fight(): void
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

    public function test_resolve_action_returns_craft_when_only_crafting_tasks_exist(): void
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

    public function test_resolve_action_returns_fight_when_only_bounty_tasks_exist(): void
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
