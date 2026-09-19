<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Illuminate\Support\Facades\Log;

class FactionLoyaltyRewardRequestService
{
    /**
     * @param BattleRewardProcessingQueueManager $queueManager
     */
    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
    ) {}

    /**
     * Enqueue a Faction Loyalty battle reward request, reusing an existing request for the same reward level.
     *
     * @param int $characterId
     * @param int $factionLoyaltyNpcId
     * @param int $rewardLevel
     * @param array $payload
     * @return ?CharacterBattleRewardRequest
     */
    public function enqueue(
        int $characterId,
        int $factionLoyaltyNpcId,
        int $rewardLevel,
        array $payload,
    ): ?CharacterBattleRewardRequest {
        $sourceId = "faction_loyalty:{$characterId}:{$factionLoyaltyNpcId}:{$rewardLevel}";

        $existing = CharacterBattleRewardRequest::query()
            ->where('character_id', $characterId)
            ->where('source_type', BattleRewardRequestSourceType::FACTION_LOYALTY)
            ->where('source_id', $sourceId)
            ->latest('id')
            ->first();

        if (! is_null($existing)) {
            Log::channel('reward_ledger')->debug('faction_loyalty.reward_request.reused', [
                'character_id' => $characterId,
                'source_id' => $sourceId,
                'faction_loyalty_npc_id' => $factionLoyaltyNpcId,
                'reward_level' => $rewardLevel,
                'status' => $existing->status->value,
            ]);

            if ($existing->status === BattleRewardRequestStatus::COMPLETED) {
                return $existing;
            }

            $this->queueManager->ensureProcessorRunning($characterId);

            return $existing;
        }

        $enqueueResult = $this->queueManager->enqueue(
            $characterId,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::FACTION_LOYALTY,
            $sourceId,
            $payload,
        );

        if (! $enqueueResult->successful()) {
            $failure = $enqueueResult->failure();

            Log::channel('reward_ledger')->error('faction_loyalty.reward_request.enqueue_failed', [
                'character_id' => $characterId,
                'source_id' => $sourceId,
                'faction_loyalty_npc_id' => $factionLoyaltyNpcId,
                'reward_level' => $rewardLevel,
                'exception_class' => is_null($failure) ? null : $failure::class,
                'exception_message' => $failure?->getMessage(),
            ]);

            return null;
        }

        $request = $enqueueResult->request();

        Log::channel('reward_ledger')->debug('faction_loyalty.reward_request.created', [
            'character_id' => $characterId,
            'request_id' => $request->id,
            'source_id' => $sourceId,
            'faction_loyalty_npc_id' => $factionLoyaltyNpcId,
            'reward_level' => $rewardLevel,
        ]);

        return $request;
    }
}
