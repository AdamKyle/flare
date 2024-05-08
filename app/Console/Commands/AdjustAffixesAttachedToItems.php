<?php

namespace App\Console\Commands;

use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;

class AdjustAffixesAttachedToItems extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adjust:affixes-attached-to-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reduces aspects of affixes';

    /**
     * Execute the console command.
     */
    public function handle() {

        ItemAffix::where('str_reduction', '>=', 1)->update([
            'str_reduction' => 0.95,
            'dur_reduction' => 0.95,
            'dex_reduction' => 0.95,
            'agi_reduction' => 0.95,
            'int_reduction' => 0.95,
            'chr_reduction' => 0.95,
            'focus_reduction' => 0.95,
        ]);

        ItemAffix::where('resistance_reduction', '>=', 1)->update([
            'resistance_reduction' => 0.95,
        ]);

        ItemAffix::where('skill_reduction', '>=', 1)->update([
            'skill_reduction' => 0.95,
        ]);

        ItemAffix::where('devouring_light', '>=', 1)->update([
            'devouring_light' => 0.95,
        ]);
    }
}
