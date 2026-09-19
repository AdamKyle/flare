<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use Illuminate\Support\Facades\Log;

class BattleRewardSecondaryUpdateService
{
    use SafelyBroadcastsEvents;

    /**
     * Dispatch the Character's coalesced non-authoritative reward follow-up updates and record their cost.
     *
     * @param int $characterId
     * @return void
     */
    public function flush(int $characterId): void
    {
        $character = Character::find($characterId);

        if (is_null($character)) {
            Log::channel('reward_processing')->warning('Secondary player updates skipped because Character no longer exists.', [
                'character_id' => $characterId,
            ]);

            return;
        }

        $totalStartedAtNs = hrtime(true);
        $topsStartedAtNs = hrtime(true);

        $topsSucceeded = $this->safelyDispatchBroadcastEvent(
            new UpdateTopBarEvent($character),
            ['character_id' => $characterId, 'secondary_update' => 'tops'],
        );

        $topsElapsedMs = intdiv(hrtime(true) - $topsStartedAtNs, 1_000_000);

        $character = Character::find($characterId);

        if (is_null($character)) {
            $this->logSecondaryUpdatesSummary($characterId, $totalStartedAtNs, $topsElapsedMs, 0, $topsSucceeded, false);

            return;
        }

        $currenciesStartedAtNs = hrtime(true);

        $currenciesSucceeded = $this->safelyDispatchBroadcastEvent(
            new UpdateCharacterCurrenciesEvent($character),
            ['character_id' => $characterId, 'secondary_update' => 'currencies'],
        );

        $currenciesElapsedMs = intdiv(hrtime(true) - $currenciesStartedAtNs, 1_000_000);

        $this->logSecondaryUpdatesSummary($characterId, $totalStartedAtNs, $topsElapsedMs, $currenciesElapsedMs, $topsSucceeded, $currenciesSucceeded);
    }

    /**
     * Log the elapsed cost and outcome of one Character's secondary reward update flush.
     *
     * @param int $characterId
     * @param int $totalStartedAtNs
     * @param int $topsElapsedMs
     * @param int $currenciesElapsedMs
     * @param bool $topsSucceeded
     * @param bool $currenciesSucceeded
     * @return void
     */
    private function logSecondaryUpdatesSummary(
        int $characterId,
        int $totalStartedAtNs,
        int $topsElapsedMs,
        int $currenciesElapsedMs,
        bool $topsSucceeded,
        bool $currenciesSucceeded,
    ): void {
        Log::channel('reward_processing')->info('Secondary player updates summary.', [
            'character_id' => $characterId,
            'tops_ms' => $topsElapsedMs,
            'currencies_ms' => $currenciesElapsedMs,
            'total_ms' => intdiv(hrtime(true) - $totalStartedAtNs, 1_000_000),
            'tops_succeeded' => $topsSucceeded,
            'currencies_succeeded' => $currenciesSucceeded,
        ]);
    }
}
