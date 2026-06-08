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
            ->orderByDesc('id')
            ->first();

        $this->deleteWarningAndLog($character, $warning);
    }

    public function dismissLatest(Character $character): array
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->orderByDesc('id')
            ->first();

        return $this->deleteWarningAndLog($character, $warning);
    }

    public function dismissSelected(Character $character, ExplorationWarning $warning): array
    {
        return $this->deleteWarningAndLog($character, $warning);
    }

    private function deleteWarningAndLog(Character $character, ?ExplorationWarning $warning): array
    {
        if (is_null($warning)) {
            return $this->getState($character);
        }

        $log = $warning->explorationLog;

        $warning->delete();

        if (! is_null($log)) {
            $log->delete();
        }

        return $this->getState($character);
    }
}
