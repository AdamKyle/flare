<?php

namespace App\Flare\Jobs;

use App\Flare\Models\ItemAffix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefactorSkillLevels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $skillLevels;

    private array $affixIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $affixIds, array $skillLevels)
    {
        $this->affixIds = $affixIds;
        $this->skillLevels = $skillLevels;
    }

    public function handle()
    {
        $itemAffixes = ItemAffix::whereIn('id', $this->affixIds)->get();

        foreach ($itemAffixes as $index => $affix) {

            $affix->update([
                'skill_level_required' => $this->skillLevels[$index]['skill_level_required'],
                'skill_level_trivial' => $this->skillLevels[$index]['skill_level_trivial'],
            ]);
        }
    }
}
