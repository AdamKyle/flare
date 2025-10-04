<?php

namespace App\Game\Events\Services;

use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;

class GlobalEventGoalCleanupService
{
    public function purgeCoreAndGoal(): void
    {
        $this->purgeByChunk(GlobalEventParticipation::class);
        $this->purgeByChunk(GlobalEventKill::class);
        $this->purgeByChunk(GlobalEventCraft::class);
        $this->purgeByChunk(GlobalEventEnchant::class);
        $this->purgeByChunk(GlobalEventGoal::class);
    }

    public function purgeEnchantInventories(): void
    {
        $this->purgeByChunk(GlobalEventCraftingInventorySlot::class);
        $this->purgeByChunk(GlobalEventCraftingInventory::class);
    }

    public function resetForSameGoal(GlobalEventGoal $goal, bool $resetNextRewardAt = true): void
    {
        if ($resetNextRewardAt) {
            $goal->update(['next_reward_at' => $goal->reward_every]);
        }

        $goalId = $goal->id;

        $this->purgeByChunk(GlobalEventParticipation::class, 1000, $goalId);
        $this->purgeByChunk(GlobalEventKill::class, 1000, $goalId);
        $this->purgeByChunk(GlobalEventCraft::class, 1000, $goalId);
        $this->purgeByChunk(GlobalEventEnchant::class, 1000, $goalId);
    }

    private function purgeByChunk(string $modelClass, int $size = 1000, ?int $goalId = null): void
    {
        $query = $modelClass::query()->select('id')->orderBy('id');

        if (! is_null($goalId) && $this->hasGoalScope($modelClass)) {
            $query->where('global_event_goal_id', $goalId);
        }

        $query->chunkById($size, function ($rows) use ($modelClass) {
            $ids = $rows->pluck('id')->all();
            if (! empty($ids)) {
                $modelClass::whereIn('id', $ids)->delete();
            }
        });
    }

    private function hasGoalScope(string $modelClass): bool
    {
        return in_array($modelClass, [
            GlobalEventParticipation::class,
            GlobalEventKill::class,
            GlobalEventCraft::class,
            GlobalEventEnchant::class,
        ], true);
    }
}
