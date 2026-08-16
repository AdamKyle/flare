<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;

class BatchCraftingAutomationLifecycleTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?BatchCraftingAutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(BatchCraftingAutomationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->service = null;
    }

    public function test_cancel_marks_the_running_batch_completed_and_cancelled(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class, BatchCraftingMonitoringUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);

        $result = $this->service->cancel($this->character);

        $batchCrafting->refresh();
        $this->assertSame(200, $result['status']);
        $this->assertFalse($batchCrafting->isRunning());
        $this->assertNotNull($batchCrafting->cancelled_at);
        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $batchCrafting->ended_reason);
        Event::assertDispatched(BatchCraftingStatusUpdated::class);
        Event::assertDispatched(BatchCraftingMonitoringUpdated::class);
    }

    public function test_cancel_returns_an_error_when_nothing_is_running(): void
    {
        $result = $this->service->cancel($this->character);

        $this->assertSame(422, $result['status']);
    }

    public function test_dismiss_marks_the_finished_batch_dismissed_and_fires_events(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class, BatchCraftingMonitoringUpdated::class]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
        ]);

        $result = $this->service->dismiss($this->character);

        $this->assertSame(200, $result['status']);
        $this->assertNotNull($batchCrafting->refresh()->panel_dismissed_at);
        Event::assertDispatched(BatchCraftingStatusUpdated::class);
        Event::assertDispatched(BatchCraftingMonitoringUpdated::class);
    }

    public function test_dismiss_returns_an_error_when_the_batch_is_still_running(): void
    {
        $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);

        $result = $this->service->dismiss($this->character);

        $this->assertSame(422, $result['status']);
    }

    public function test_dismiss_returns_an_error_when_there_is_no_visible_batch(): void
    {
        $result = $this->service->dismiss($this->character);

        $this->assertSame(422, $result['status']);
    }

    public function test_acknowledge_info_marks_info_as_acknowledged_without_duplicating_the_marker_row(): void
    {
        $this->service->acknowledgeInfo($this->character);
        $this->service->acknowledgeInfo($this->character);

        $this->assertSame(1, BatchCrafting::where('character_id', $this->character->id)->where('status', BatchCraftingStatus::INFO->value)->count());
    }

    public function test_acknowledge_info_creates_a_completed_and_dismissed_info_row(): void
    {
        $this->service->acknowledgeInfo($this->character);

        $infoRow = BatchCrafting::where('character_id', $this->character->id)->where('status', BatchCraftingStatus::INFO->value)->first();
        $this->assertNotNull($infoRow->completed_at);
        $this->assertNotNull($infoRow->panel_dismissed_at);
        $this->assertTrue($infoRow->info_acknowledged);
    }

    public function test_complete_for_death_ends_the_running_batch_with_died_reason(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);

        $this->service->completeForDeath($this->character);

        $this->assertSame(BatchCraftingEndReason::DIED->value, $batchCrafting->refresh()->ended_reason);
        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_complete_for_death_does_nothing_when_nothing_is_running(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);

        $this->service->completeForDeath($this->character);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }
}
