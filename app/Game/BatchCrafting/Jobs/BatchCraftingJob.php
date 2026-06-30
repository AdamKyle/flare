<?php

namespace App\Game\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchCraftingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $batchCraftingId) {}

    public function handle(BatchCraftingService $batchCraftingService): void
    {
        $batchCrafting = BatchCrafting::find($this->batchCraftingId);

        if (is_null($batchCrafting)) {
            return;
        }

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

        $delaySeconds = (int) (($batchCrafting->progress ?? [])['tick_delay_seconds'] ?? 60);

        self::dispatch($batchCrafting->id)->delay(now()->addSeconds($delaySeconds))->onConnection('long_running')->onQueue('default_long');
    }
}
