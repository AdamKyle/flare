<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Game\Automation\Events\ExplorationWarningState;

class ExplorationWarningService
{
    public function getState(Character $character): array
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->whereNull('dismissed_at')
            ->orderByDesc('id')
            ->first();

        $warnings = [];

        if (! is_null($warning)) {
            $warnings[] = [
                'id' => $warning->id,
                'type' => $warning->type,
                'message' => $warning->message,
            ];
        }

        $hasWarning = count($warnings) > 0;

        event(new ExplorationWarningState($character->user, $hasWarning, $warnings));

        return [
            'has_warning' => $hasWarning,
            'warnings' => $warnings,
        ];
    }

    public function createWarning(Character $character, ExplorationLog $log, string $type, string $message): array
    {
        ExplorationWarning::create([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'exploration_log_id' => $log->id,
            'type' => $type,
            'message' => $message,
        ]);

        return $this->getState($character);
    }

    public function dismiss(Character $character, ?int $warningId = null): void
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->when(! is_null($warningId), fn ($query) => $query->where('id', $warningId))
            ->whereNull('dismissed_at')
            ->orderByDesc('id')
            ->first();

        $this->dismissWarning($character, $warning);
    }

    public function dismissLatest(Character $character): array
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->whereNull('dismissed_at')
            ->orderByDesc('id')
            ->first();

        return $this->dismissWarning($character, $warning);
    }

    public function dismissSelected(Character $character, ExplorationWarning $warning): array
    {
        return $this->dismissWarning($character, $warning);
    }

    private function dismissWarning(Character $character, ?ExplorationWarning $warning): array
    {
        if (is_null($warning)) {
            return $this->getState($character);
        }

        $warning->update([
            'dismissed_at' => now(),
        ]);

        if (! is_null($warning->exploration_log_id)) {
            ExplorationLog::where('id', $warning->exploration_log_id)
                ->whereNull('panel_dismissed_at')
                ->update(['panel_dismissed_at' => now()]);
        }

        return $this->getState($character);
    }
}
