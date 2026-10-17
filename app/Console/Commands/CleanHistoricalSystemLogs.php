<?php

namespace App\Console\Commands;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Flare\Values\AutomationType;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class CleanHistoricalSystemLogs extends Command
{
    // delve_logs must remain because DelveTopsService reads them, while kingdom_logs retains its dedicated seven-day command.
    protected $signature = 'clean:historical-system-logs
                            {--dry-run : Count eligible records without deleting them}
                            {--batch-size=500 : Maximum records per deletion batch}';

    protected $description = 'Remove eligible historical operational logs older than 30 days.';

    public function handle(): int
    {
        $batchSize = filter_var($this->option('batch-size'), FILTER_VALIDATE_INT);

        if ($batchSize === false || $batchSize < 1) {
            $this->error('The batch size must be a positive integer.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays(30);
        $dryRun = (bool) $this->option('dry-run');

        $this->line('Cutoff: '.$cutoff->toDateTimeString());
        $this->line('Mode: '.($dryRun ? 'DRY RUN' : 'DELETE'));

        if (! $dryRun) {
            $this->line('Batch size: '.$batchSize);
        }

        try {
            $counts = $dryRun
                ? $this->eligibleCounts($cutoff)
                : $this->deleteEligibleRecords($cutoff, $batchSize);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Historical system log cleanup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        foreach ($counts as $table => $count) {
            $this->line($table.': '.$count);
        }

        $this->line('Total: '.array_sum($counts));

        return self::SUCCESS;
    }

    private function eligibleCounts(CarbonInterface $cutoff): array
    {
        return [
            'failed_jobs' => $this->failedJobsQuery($cutoff)->count(),
            'batch_craftings' => $this->batchCraftingsQuery($cutoff)->count(),
            'character_battle_reward_requests' => $this->rewardRequestsQuery($cutoff)->count(),
            'monitored_system_error_occurrences' => $this->errorOccurrencesQuery($cutoff)->count(),
            'exploration_warnings' => $this->explorationWarningsQuery($cutoff)->count(),
            'faction_loyalty_automation_logs' => $this->factionLoyaltyAutomationLogsQuery($cutoff)->count(),
        ];
    }

    private function deleteEligibleRecords(CarbonInterface $cutoff, int $batchSize): array
    {
        $eligibleCounts = $this->eligibleCounts($cutoff);

        return [
            'failed_jobs' => $this->deleteWithProgress(
                'failed_jobs',
                $eligibleCounts['failed_jobs'],
                fn (ProgressBar $progressBar): int => $this->deleteSimpleBatches(
                    $this->failedJobsQuery($cutoff),
                    'failed_jobs',
                    $batchSize,
                    $progressBar,
                ),
            ),
            'batch_craftings' => $this->deleteWithProgress(
                'batch_craftings',
                $eligibleCounts['batch_craftings'],
                fn (ProgressBar $progressBar): int => $this->deleteSimpleBatches(
                    $this->batchCraftingsQuery($cutoff),
                    'batch_craftings',
                    $batchSize,
                    $progressBar,
                ),
            ),
            'character_battle_reward_requests' => $this->deleteWithProgress(
                'character_battle_reward_requests',
                $eligibleCounts['character_battle_reward_requests'],
                fn (ProgressBar $progressBar): int => $this->deleteRewardRequestBatches(
                    $cutoff,
                    $batchSize,
                    $progressBar,
                ),
            ),
            'monitored_system_error_occurrences' => $this->deleteWithProgress(
                'monitored_system_error_occurrences',
                $eligibleCounts['monitored_system_error_occurrences'],
                fn (ProgressBar $progressBar): int => $this->deleteSimpleBatches(
                    $this->errorOccurrencesQuery($cutoff),
                    'monitored_system_error_occurrences',
                    $batchSize,
                    $progressBar,
                ),
            ),
            'exploration_warnings' => $this->deleteWithProgress(
                'exploration_warnings',
                $eligibleCounts['exploration_warnings'],
                fn (ProgressBar $progressBar): int => $this->deleteSimpleBatches(
                    $this->explorationWarningsQuery($cutoff),
                    'exploration_warnings',
                    $batchSize,
                    $progressBar,
                ),
            ),
            'faction_loyalty_automation_logs' => $this->deleteWithProgress(
                'faction_loyalty_automation_logs',
                $eligibleCounts['faction_loyalty_automation_logs'],
                fn (ProgressBar $progressBar): int => $this->deleteSimpleBatches(
                    $this->factionLoyaltyAutomationLogsQuery($cutoff),
                    'faction_loyalty_automation_logs',
                    $batchSize,
                    $progressBar,
                ),
            ),
        ];
    }

    private function failedJobsQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('failed_jobs')->where('failed_at', '<', $cutoff);
    }

    private function batchCraftingsQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('batch_craftings')
            ->where(function (Builder $query) use ($cutoff): void {
                $query->whereNotNull('completed_at')->where('completed_at', '<', $cutoff)
                    ->orWhere(function (Builder $cancelled) use ($cutoff): void {
                        $cancelled->whereNotNull('cancelled_at')->where('cancelled_at', '<', $cutoff);
                    });
            })
            ->where(function (Builder $query): void {
                $query->whereNotNull('completed_at')->orWhereNotNull('cancelled_at');
            });
    }

    private function rewardRequestsQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('character_battle_reward_requests')
            ->whereIn('status', [
                BattleRewardRequestStatus::COMPLETED->value,
                BattleRewardRequestStatus::FAILED->value,
            ])
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff);
    }

    private function errorOccurrencesQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('monitored_system_error_occurrences')
            ->whereNotNull('occurred_at')
            ->where('occurred_at', '<', $cutoff);
    }

    private function explorationWarningsQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('exploration_warnings')
            ->leftJoin('exploration_logs', 'exploration_logs.id', '=', 'exploration_warnings.exploration_log_id')
            ->whereNotNull('exploration_warnings.dismissed_at')
            ->where('exploration_warnings.created_at', '<', $cutoff)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('character_automations')
                    ->whereColumn('character_automations.id', 'exploration_logs.character_automation_id')
                    ->where('character_automations.type', AutomationType::EXPLORING)
                    ->where('character_automations.completed_at', '>', now());
            })
            ->select('exploration_warnings.id');
    }

    private function factionLoyaltyAutomationLogsQuery(CarbonInterface $cutoff): Builder
    {
        return DB::table('faction_loyalty_automation_logs')
            ->join(
                'faction_loyalty_automations',
                'faction_loyalty_automations.id',
                '=',
                'faction_loyalty_automation_logs.faction_loyalty_automation_id',
            )
            ->whereNotNull('faction_loyalty_automations.completed_at')
            ->where('faction_loyalty_automations.completed_at', '<', $cutoff)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('character_automations')
                    ->whereColumn(
                        'character_automations.id',
                        'faction_loyalty_automations.character_automation_id',
                    )
                    ->where('character_automations.type', AutomationType::FACTION_LOYALTY)
                    ->where('character_automations.completed_at', '>', now());
            })
            ->select('faction_loyalty_automation_logs.id');
    }

    private function deleteWithProgress(string $table, int $eligibleCount, Closure $delete): int
    {
        $this->line($table);
        $progressBar = $this->output->createProgressBar($eligibleCount);
        $progressBar->start();
        $deleted = $delete($progressBar);
        $progressBar->finish();
        $this->newLine();

        return $deleted;
    }

    private function deleteSimpleBatches(
        Builder $query,
        string $table,
        int $batchSize,
        ProgressBar $progressBar,
    ): int
    {
        $deleted = 0;

        while (true) {
            $ids = (clone $query)->orderBy($table.'.id')->limit($batchSize)->pluck('id');

            if ($ids->isEmpty()) {
                return $deleted;
            }

            $deletedInBatch = DB::table($table)->whereIn('id', $ids)->delete();
            $deleted += $deletedInBatch;
            $progressBar->advance($deletedInBatch);
        }
    }

    private function deleteRewardRequestBatches(
        CarbonInterface $cutoff,
        int $batchSize,
        ProgressBar $progressBar,
    ): int
    {
        $deleted = 0;

        while (true) {
            $ids = $this->rewardRequestsQuery($cutoff)->orderBy('id')->limit($batchSize)->pluck('id');

            if ($ids->isEmpty()) {
                return $deleted;
            }

            $deletedInBatch = DB::transaction(function () use ($ids): int {
                DB::table('character_battle_reward_request_messages')
                    ->whereIn('character_battle_reward_request_id', $ids)
                    ->delete();
                DB::table('character_battle_reward_request_steps')
                    ->whereIn('character_battle_reward_request_id', $ids)
                    ->delete();

                return DB::table('character_battle_reward_requests')->whereIn('id', $ids)->delete();
            });
            $deleted += $deletedInBatch;
            $progressBar->advance($deletedInBatch);
        }
    }
}
