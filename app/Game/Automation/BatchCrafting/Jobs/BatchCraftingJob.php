<?php

namespace App\Game\Automation\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
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

    public function __construct(public readonly int $batchCraftingId)
    {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    /**
     * Process the queued Batch Crafting run and re-dispatch itself when a recurring window remains.
     *
     * @param  BatchCraftingAutomationService  $batchCraftingAutomationService  The Batch Crafting lifecycle service.
     * @return void This method does not return a value.
     */
    public function handle(BatchCraftingAutomationService $batchCraftingAutomationService): void
    {
        $batchCrafting = BatchCrafting::find($this->batchCraftingId);

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return;
        }

        $nextAttemptAt = $batchCraftingAutomationService->process($batchCrafting);

        if (! is_null($nextAttemptAt) && ! app()->runningUnitTests()) {
            $this->redispatch($nextAttemptAt); // @codeCoverageIgnore
        }
    }

    /**
     * Re-dispatch this job for the next recurring execution window.
     *
     * @param  Carbon  $nextAttemptAt  The next execution time to schedule.
     * @return void This method does not return a value.
     *
     * @codeCoverageIgnore
     */
    private function redispatch(Carbon $nextAttemptAt): void
    {
        self::dispatch($this->batchCraftingId)->delay($nextAttemptAt);
    }
}
