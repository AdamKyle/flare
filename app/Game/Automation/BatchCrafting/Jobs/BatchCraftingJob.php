<?php

namespace App\Game\Automation\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
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

    /**
     * @param  int  $batchCraftingId  The BatchCrafting record identifier to process.
     */
    public function __construct(public readonly int $batchCraftingId)
    {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    /**
     * Process one queued Batch Crafting operation for the given run.
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

        $batchCraftingAutomationService->processOneOperation($batchCrafting);
    }
}
