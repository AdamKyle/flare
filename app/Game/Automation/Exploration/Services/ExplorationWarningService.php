<?php

namespace App\Game\Automation\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Game\Automation\Exploration\Events\ExplorationWarningState;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ExplorationWarningService
{
    /**
     * Return and broadcast the character's current Exploration warning state.
     *
     * @param  Character  $character  The character to resolve warning state for.
     * @return array The current Exploration warning state.
     */
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

    /**
     * Record a new Exploration warning for the character, retrying past transient database locks.
     *
     * @param  Character  $character  The character receiving the warning.
     * @param  ExplorationLog  $log  The Exploration log the warning relates to.
     * @param  string  $type  The warning type.
     * @param  string  $message  The warning message.
     * @return array The updated Exploration warning state.
     */
    public function createWarning(Character $character, ExplorationLog $log, string $type, string $message): array
    {
        try {
            ExplorationWarning::create([
                'character_id' => $character->id,
                'user_id' => $character->user_id,
                'exploration_log_id' => $log->id,
                'type' => $type,
                'message' => $message,
            ]);
        } catch (QueryException $exception) {
            $exceptionCode = (int) $exception->getCode();
            $previousCode = (int) ($exception->getPrevious()?->getCode() ?? 0);
            $isRetryable = in_array($exceptionCode, [1205, 1213], true)
                || in_array($previousCode, [1205, 1213], true)
                || str_contains($exception->getMessage(), '1205')
                || str_contains($exception->getMessage(), '1213')
                || str_contains($exception->getMessage(), 'Lock wait timeout exceeded')
                || str_contains($exception->getMessage(), 'Deadlock found');

            if (! $isRetryable) {
                throw $exception;
            }

            Log::warning('Exploration warning creation skipped after database lock error.', [
                'character_id' => $character->id,
                'exploration_log_id' => $log->id,
                'type' => $type,
                'exception_code' => $exceptionCode !== 0 ? $exceptionCode : $previousCode,
            ]);
        }

        return $this->getState($character);
    }

    /**
     * Dismiss the character's latest undismissed Exploration warning, or a specific one by id.
     *
     * @param  Character  $character  The character dismissing the warning.
     * @param  int|null  $warningId  The specific warning id to dismiss, or null for the latest.
     * @return void This method does not return a value.
     */
    public function dismiss(Character $character, ?int $warningId = null): void
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->when(! is_null($warningId), fn ($query) => $query->where('id', $warningId))
            ->whereNull('dismissed_at')
            ->orderByDesc('id')
            ->first();

        $this->dismissWarning($character, $warning);
    }

    /**
     * Dismiss the character's latest undismissed Exploration warning.
     *
     * @param  Character  $character  The character dismissing the warning.
     * @return array The updated Exploration warning state.
     */
    public function dismissLatest(Character $character): array
    {
        $warning = ExplorationWarning::where('character_id', $character->id)
            ->whereNull('dismissed_at')
            ->orderByDesc('id')
            ->first();

        return $this->dismissWarning($character, $warning);
    }

    /**
     * Dismiss a specific Exploration warning for the character.
     *
     * @param  Character  $character  The character dismissing the warning.
     * @param  ExplorationWarning  $warning  The specific warning to dismiss.
     * @return array The updated Exploration warning state.
     */
    public function dismissSelected(Character $character, ExplorationWarning $warning): array
    {
        return $this->dismissWarning($character, $warning);
    }

    /**
     * Mark the character's undismissed Exploration warnings as dismissed and return the updated state.
     *
     * @param  Character  $character  The character whose warnings are being dismissed.
     * @param  ExplorationWarning|null  $warning  Unused; dismissal always clears all undismissed warnings.
     * @return array The updated Exploration warning state.
     */
    private function dismissWarning(Character $character, ?ExplorationWarning $warning): array
    {
        ExplorationWarning::where('character_id', $character->id)
            ->whereNull('dismissed_at')
            ->get()
            ->each(function (ExplorationWarning $explorationWarning): void {
                $explorationWarning->update(['dismissed_at' => now()]);

                if (! is_null($explorationWarning->exploration_log_id)) {
                    ExplorationLog::where('id', $explorationWarning->exploration_log_id)
                        ->whereNull('panel_dismissed_at')
                        ->update(['panel_dismissed_at' => now()]);
                }
            });

        return $this->getState($character);
    }
}
