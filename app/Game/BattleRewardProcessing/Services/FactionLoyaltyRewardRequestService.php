<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Illuminate\Support\Facades\Log;

class FactionLoyaltyRewardRequestService
{
    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
    ) {}

    public function enqueue(
        int $characterId,
        int $factionLoyaltyNpcId,
        int $rewardLevel,
        array $payload,
    ): CharacterBattleRewardRequest {
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

        $request = $this->queueManager->enqueue(
            $characterId,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::FACTION_LOYALTY,
            $sourceId,
            $payload,
        );

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
