<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Models\SmeltingProgress;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Jobs\SmeltSteel;
use Carbon\Carbon;

class SteelSmeltingService
{
    use ResponseBuilder;

    private UpdateKingdom $updateKingdom;

    public function __construct(UpdateKingdom $updateKingdom)
    {
        $this->updateKingdom = $updateKingdom;
    }

    public function smeltSteel(int $amount, Kingdom $kingdom): array
    {

        $newAmount = $amount * 2;

        if ($this->notEnoughIron($newAmount, $kingdom)) {
            return $this->errorResult('Not enough iron.');
        }

        if ($kingdom->current_steel >= $kingdom->max_steel) {
            return $this->errorResult('Not enough storage for more steel. You are maxed out.');
        }

        $this->fireOffSmeltingJob($newAmount, $amount, $kingdom);

        return $this->successResult([]);
    }

    public function cancelSmeltingEvent(Kingdom $kingdom): array
    {
        $smeltingQueue = SmeltingProgress::where('kingdom_id', $kingdom->id)->first();

        if (is_null($smeltingQueue)) {
            return $this->errorResult('No smelting in progress for this kingdom.');
        }

        $start = Carbon::parse($smeltingQueue->started_at)->timestamp;
        $end = Carbon::parse($smeltingQueue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $completed = (($current - $start) / ($end - $start));

        if ($completed === 0) {
            return $this->errorResult('Cannot cancel this smelting event. Almost done.');
        }

        $totalPercentage = 1 - $completed;

        $amountSmelting = $smeltingQueue->amount_to_smelt;
        $amountToGetBack = $amountSmelting - ($amountSmelting * $totalPercentage);

        $currentIron = $kingdom->current_iron;
        $newIron = $currentIron + $amountToGetBack;

        if ($newIron > $kingdom->max_iron) {
            $newIron = $kingdom->max_iron;
        }

        $kingdom->update([
            'current_iron' => $newIron,
        ]);

        $kingdom = $kingdom->refresh();

        $smeltingQueue->delete();

        $this->updateKingdom->updateKingdom($kingdom);

        return $this->successResult();
    }

    protected function notEnoughIron(int $amount, Kingdom $kingdom): bool
    {
        return $amount > $kingdom->current_iron;
    }

    protected function fireOffSmeltingJob(int $amount, int $originalAmount, Kingdom $kingdom): void
    {

        $time = ($originalAmount / 100) >> 0;
        $time = $time * 5;
        $time = $time - ($time * $kingdom->fetchSmeltingTimeReduction());

        $kingdom->update([
            'current_iron' => $kingdom->current_iron - $amount,
        ]);

        $kingdom = $kingdom->refresh();

        $smeltingJob = SmeltingProgress::create([
            'character_id' => $kingdom->character_id,
            'kingdom_id' => $kingdom->id,
            'started_at' => now(),
            'completed_at' => now()->addMinutes($time),
            'amount_to_smelt' => $originalAmount,
        ]);

        SmeltSteel::dispatch($smeltingJob->id)->delay(now()->addMinutes($time))->onQueue('default_long');

        $this->updateKingdom->updateKingdom($kingdom);
    }
}
