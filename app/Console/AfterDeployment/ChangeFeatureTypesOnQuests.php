<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class ChangeFeatureTypesOnQuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:feature-types-on-quests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change feature types on quests';

    /**
     * Execute the console command.
     */
    public function handle() {
        $oldReincarnation = 1;
        $oldCosmeticText  = 2;
        $oldNameTag       = 3;

        $newReincarnation = 0;
        $newCosmeticText  = 1;
        $newNameTag       = 2;

        Quest::where('unlocks_feature', $oldReincarnation)->update(['unlocks_feature' => $newReincarnation]);
        Quest::where('unlocks_feature', $oldCosmeticText)->update(['unlocks_feature' => $newCosmeticText]);
        Quest::where('unlocks_feature', $oldNameTag)->update(['unlocks_feature' => $newNameTag]);
    }
}
