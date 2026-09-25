<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class BattleRewardPresentationQueueManager
{
    /**
     * Outlives the 300 second presentation worker timeout but expires well before the 1200 second battle_reward_processing retry_after, so a killed worker's lock never blocks the queue retry of its job.
     */
    private const PRESENTATION_LOCK_SECONDS = 360;

    /**
     * Build the Character's presentation lock, which is separate from the authoritative reward mutation lock.
     *
     * @param int $characterId
     * @return Lock
     */
    public function processorLock(int $characterId): Lock
    {
        return Cache::lock('character-reward-presentation:'.$characterId, self::PRESENTATION_LOCK_SECONDS);
    }

    /**
     * Return the Character's oldest completed reward request whose presentation has not finished.
     *
     * @param int $characterId
     * @return ?CharacterBattleRewardRequest
     */
    public function nextRequest(int $characterId): ?CharacterBattleRewardRequest
    {
        return $this->pendingPresentationQuery($characterId)->orderBy('id')->first();
    }

    /**
     * Determine whether the Character has a completed reward request whose presentation has not finished.
     *
     * @param int $characterId
     * @return bool
     */
    public function hasPendingRequests(int $characterId): bool
    {
        return $this->pendingPresentationQuery($characterId)->exists();
    }

    /**
     * Build the query for the Character's completed reward requests with an unfinished final player update or message outbox step.
     *
     * @param int $characterId
     * @return Builder
     */
    private function pendingPresentationQuery(int $characterId): Builder
    {
        return CharacterBattleRewardRequest::query()
            ->forCharacter($characterId)
            ->completed()
            ->whereHas('steps', fn (Builder $query): Builder => $query
                ->whereIn('step_name', [
                    BattleRewardStepName::FINAL_PLAYER_UPDATES->value,
                    BattleRewardStepName::MESSAGE_OUTBOX->value,
                ])
                ->where('status', '!=', BattleRewardStepStatus::COMPLETED->value));
    }
}
