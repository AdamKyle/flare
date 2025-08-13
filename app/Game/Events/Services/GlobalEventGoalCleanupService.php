<?php

namespace App\Game\Events\Services;

use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;

class GlobalEventGoalCleanupService
{
    /**
     * @return void
     */
    public function truncateAll(): void
    {
        GlobalEventParticipation::truncate();
        GlobalEventGoal::truncate();
        GlobalEventCraftingInventorySlot::truncate();
        GlobalEventKill::truncate();
        GlobalEventCraft::truncate();
        GlobalEventEnchant::truncate();
    }
}
