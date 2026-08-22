<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Setup;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Setup\EnchantBatchCraftingSetupService;
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

class EnchantBatchCraftingSetupServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateScheduledEvent, RefreshDatabase;

    private ?EnchantBatchCraftingSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(EnchantBatchCraftingSetupService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_supports_only_enchant(): void
    {
        $this->assertTrue($this->service->supports(BatchCraftingType::ENCHANT));
        $this->assertFalse($this->service->supports(BatchCraftingType::CRAFT_AND_ENCHANT));
    }

    public function test_preview_is_always_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $validated = [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ];

        $this->assertNull($this->service->preview($character, $validated));
    }

    public function test_resolve_start_reports_a_blocker_when_no_eligible_event_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $validated = [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ];

        $result = $this->service->resolveStart($character, $validated);

        $this->assertNotEmpty($result['blockers']);
        $this->assertNull($result['progress']['event_goal_id']);
        $this->assertSame('enchant_event_inventory', $result['progress']['event_enchant_phase']);
        $this->assertSame(0, $result['progress']['fallback_cycle_position']);
    }

    public function test_resolve_start_persists_the_authoritative_goal_id_when_eligible(): void
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

        $validated = [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ];

        $result = $this->service->resolveStart($character, $validated);

        $this->assertSame([], $result['blockers']);
        $this->assertSame($goal->id, $result['progress']['event_goal_id']);
        $this->assertSame(0, $result['progress']['enchanting_xp_gained']);
        $this->assertSame(0, $result['progress']['crafting_xp_gained']);
        $this->assertNull($result['progress']['current_item_id']);
    }
}
