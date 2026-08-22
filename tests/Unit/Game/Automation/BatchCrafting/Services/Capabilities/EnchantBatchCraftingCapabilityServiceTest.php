<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Game\Automation\BatchCrafting\Services\Capabilities\EnchantBatchCraftingCapabilityService;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateScheduledEvent;

class EnchantBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateScheduledEvent, RefreshDatabase;

    private ?EnchantBatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(EnchantBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_is_false_and_null_when_no_event_is_eligible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_enchant_for_event']);
        $this->assertNull($result['enchant_event_goal']);
    }

    public function test_build_is_true_and_populated_when_a_current_event_is_eligible(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = $this->service->build($character);

        $this->assertTrue($result['can_enchant_for_event']);
        $this->assertNotNull($result['enchant_event_goal']);
        $this->assertSame($goal->id, $result['enchant_event_goal']['goal_id']);
    }

    public function test_event_goal_facts_by_id_returns_null_when_the_goal_no_longer_exists(): void
    {
        $result = $this->service->eventGoalFactsById(
            (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(),
            999999
        );

        $this->assertNull($result);
    }

    public function test_event_goal_facts_by_id_returns_the_goal_facts_when_the_goal_exists(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = $this->service->eventGoalFactsById($character, $goal->id);

        $this->assertNotNull($result);
        $this->assertSame($goal->id, $result['goal_id']);
        $this->assertSame($event->id, $result['event_id']);
        $this->assertSame(100, $result['max_enchants']);
        $this->assertSame(0, $result['character_contribution']);
    }
}
