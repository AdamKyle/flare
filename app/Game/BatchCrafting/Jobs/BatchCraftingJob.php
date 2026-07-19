<?php

namespace App\Game\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchCraftingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CONNECTION = 'long_running';

    public const QUEUE = 'batch_crafting';

    public function __construct(private readonly int $batchCraftingId)
    {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    public function handle(BatchCraftingService $batchCraftingService): void
    {
        $batchCrafting = BatchCrafting::find($this->batchCraftingId);

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return;
        }

        $batchCraftingService->markProcessing($batchCrafting);

        $batchCrafting = $batchCraftingService->process($batchCrafting);

        if ($batchCrafting->isRunning() && ! app()->runningUnitTests()) {
            $this->redispatchForNextRun($batchCrafting); // @codeCoverageIgnore
        }
    }

    /** @codeCoverageIgnore */
    private function redispatchForNextRun(BatchCrafting $batchCrafting): void
    {
        if (! $batchCrafting->isRunning()) {
            return;
        }

        $progress = $batchCrafting->progress ?? [];
        $nextAttemptAt = $progress['next_attempt_at'] ?? null;

        if (! is_null($nextAttemptAt)) {
            self::dispatch($batchCrafting->id)->delay(Carbon::parse($nextAttemptAt));

            return;
        }

        $delaySeconds = (int) ($progress['tick_delay_seconds'] ?? 60);

        self::dispatch($batchCrafting->id)->delay(now()->addSeconds($delaySeconds));
    }
}
