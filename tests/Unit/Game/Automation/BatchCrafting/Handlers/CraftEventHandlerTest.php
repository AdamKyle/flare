<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftEventHandler;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class CraftEventHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?CraftEventHandler $handler;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(CraftEventHandler::class);

        $this->progress = [
            'craft_mode' => 'event',
            'event_cycle_position' => 0,
            'crafting_xp_gained' => 0,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->handler = null;
    }

    public function test_handle_ends_event_not_running_when_no_eligible_goal_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_NOT_RUNNING, $result->endReason());
    }

    public function test_handle_ends_event_step_changed_when_the_event_has_moved_past_crafting(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_STEP_CHANGED, $result->endReason());
    }

    public function test_handle_ends_event_goal_complete_when_the_goal_is_already_saturated(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 1,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_crafts' => 1]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE, $result->endReason());
    }

    public function test_handle_crafts_and_contributes_the_weapon_target_when_eligible(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingActionStatus::CRAFTED, $result->actionStatus());
        $this->assertNull($result->endReason());
        $this->assertSame(1, $progress['event_cycle_position']);
    }

    public function test_handle_translates_a_failed_craft_roll_into_a_failed_result(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $result = resolve(CraftEventHandler::class)->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
    }

    public function test_handle_returns_skipped_when_no_eligible_item_exists_for_the_target(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingActionStatus::SKIPPED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertSame(0, $result->goldSpent());
    }

    public function test_handle_ends_event_goal_complete_when_the_contribution_reaches_max_crafts(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 1,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE, $result->endReason());
    }

    public function test_handle_crafts_a_non_weapon_target_using_the_target_crafting_type(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->progress['event_cycle_position'] = 1;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingActionStatus::CRAFTED, $result->actionStatus());
        $this->assertSame('armour', $progress['current_crafting_type']);
    }

    public function test_handle_crafts_the_ring_target_using_the_ring_crafting_type(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->progress['event_cycle_position'] = 2;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingActionStatus::CRAFTED, $result->actionStatus());
        $this->assertSame('ring', $progress['current_crafting_type']);
    }

    public function test_handle_crafts_the_damage_spell_target_using_the_spell_crafting_type(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->progress['event_cycle_position'] = 3;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingActionStatus::CRAFTED, $result->actionStatus());
        $this->assertSame('spell', $progress['current_crafting_type']);
    }

    public function test_handle_ends_event_step_changed_when_the_event_step_changes_concurrently_after_contribution(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->createItem(['name' => 'Event Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $eligibilityService = Mockery::mock(GlobalEventGoalEligibilityService::class)->makePartial();
        $eligibilityService->shouldReceive('currentCraftingGoalFor')->once()->andReturn($goal);
        $eligibilityService->shouldReceive('currentCraftingGoalFor')->once()->andReturnUsing(function () use ($event) {
            $event->update(['current_event_goal_step' => GlobalEventSteps::BATTLE]);

            return null;
        });
        $this->app->instance(GlobalEventGoalEligibilityService::class, $eligibilityService);

        $result = resolve(CraftEventHandler::class)->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_STEP_CHANGED, $result->endReason());
    }

    public function test_handle_ends_event_wrong_map_when_the_characters_own_event_map_is_not_the_canonical_one(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $secondGameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $secondGameMap)->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_WRONG_MAP, $result->endReason());
    }

    public function test_handle_ends_event_no_craftable_items_when_the_goal_has_no_craft_capacity(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS, $result->endReason());
    }
}
