<?php

namespace Tests\Traits;

use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;

trait CreateFactionLoyalty
{
    public function createFactionLoyalty(array $options = []): FactionLoyalty
    {
        return FactionLoyalty::factory()->create($options);
    }

    public function createFactionLoyaltyNpc(array $options = []): FactionLoyaltyNpc
    {
        return FactionLoyaltyNpc::factory()->create($options);
    }

    public function createFactionLoyaltyNpcTask(array $options = []): FactionLoyaltyNpcTask
    {
        return FactionLoyaltyNpcTask::factory()->create($options);
    }
}
