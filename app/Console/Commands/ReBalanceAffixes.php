<?php

namespace App\Console\Commands;

use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;

class ReBalanceAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 're-balance:affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalances the affixes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fieldsToRebalance = [
            'str_mod',
            'int_mod',
            'dex_mod',
            'chr_mod',
            'agi_mod',
            'focus_mod',
            'dur_mod',
        ];

        foreach ($fieldsToRebalance as $field) {
            $affixes = ItemAffix::where($field, '>', 0)->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

            $min = 0.01;
            $max = 0.50;

            $increments = $max / $affixes->count();

            $values = range($min, $max, $increments);

            foreach ($affixes as $index => $affix) {

                if (isset($values[$index])) {
                    $affix->{$field} = $values[$index];
                } else {
                    $affix->{$field} = $max;
                }

                $affix->save();
            }
        }
    }
}
