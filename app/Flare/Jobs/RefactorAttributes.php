<?php

namespace App\Flare\Jobs;

use App\Flare\Models\ItemAffix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefactorAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $affixIds;

    private array $curve;

    private int $maxValue = 2000000000;

    private string $attribute;

    /**
     * Create a new job instance.
     */
    public function __construct(array $affixIds, array $curve, string $attribute)
    {
        $this->affixIds = $affixIds;
        $this->curve = $curve;
        $this->attribute = $attribute;
    }

    public function handle()
    {
        $itemAffixes = ItemAffix::whereIn('id', $this->affixIds)->get();

        foreach ($itemAffixes as $index => $affix) {

            $affix->update([
                $this->attribute => $this->curve[$index],
            ]);
        }
    }
}
