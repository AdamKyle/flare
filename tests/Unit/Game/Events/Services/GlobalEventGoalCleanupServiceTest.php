<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Game\Events\Services\GlobalEventGoalCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalEventGoalCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?GlobalEventGoalCleanupService $service = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(GlobalEventGoalCleanupService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function testTruncateAll(): void
    {
        $this->service->purgeEnchantInventories();
        $this->service->purgeCoreAndGoal();

        $this->assertSame(0, GlobalEventParticipation::count());
        $this->assertSame(0, GlobalEventGoal::count());
        $this->assertSame(0, GlobalEventCraftingInventorySlot::count());
        $this->assertSame(0, GlobalEventKill::count());
        $this->assertSame(0, GlobalEventCraft::count());
        $this->assertSame(0, GlobalEventEnchant::count());
    }
}
