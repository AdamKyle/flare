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
    public function handle() {
        $this->reBalanceBaseAttributes();
        $this->reBalanceReductions();
        $this->reBalanceIntRequired();
        $this->reBalanceAttributes();
        $this->reBalanceFightAndMovementTimeOut();
        $this->reBalanceDevouringLight();
        $this->reBalanceDamage();
        $this->reBalanceSkillBonuses();
    }

    public function reBalanceBaseAttributes() {
        $statFieldsToBalance = [
            'str_mod',
            'int_mod',
            'dex_mod',
            'chr_mod',
            'agi_mod',
            'focus_mod',
            'dur_mod',
        ];

        foreach ($statFieldsToBalance as $field) {

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

    public function reBalanceReductions() {
        $reductions = [
            'str_reduction',
            'dur_reduction',
            'dex_reduction',
            'chr_reduction',
            'int_reduction',
            'agi_reduction',
            'focus_reduction',
        ];

        foreach ($reductions as $field) {

            $affixes = ItemAffix::where($field, '>', 0)->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

            $min = 0.05;
            $max = 0.75;

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

    public function reBalanceAttributes() {
        $attributes = [
            'base_damage_mod',
            'base_ac_mod',
            'base_healing_mod',
            'skill_bonus',
            'skill_reduction',
            'resistance_reduction',
            'base_damage_mod_bonus',
            'base_healing_mod_bonus',
            'base_ac_mod_bonus',
        ];

        foreach ($attributes as $field) {

            $affixes = ItemAffix::where($field, '>', 0)->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

            $min = 0.01;
            $max = 0.30;

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

    public function reBalanceIntRequired() {
        $affixes = ItemAffix::where('randomly_generated', false)->orderBy('int_required', 'asc')->get();

        $min = 5;
        $max = 5000;

        $increments = round($max / $affixes->count());

        $values = range($min, $max, $increments);

        foreach ($affixes as $index => $affix) {
            if (isset($values[$index])) {
                $affix->int_required = $values[$index];
            } else {
                $affix->int_required = $max;
            }

            $affix->save();
        }
    }

    public function reBalanceDevouringLight() {
        $affixes = ItemAffix::where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

        $min = 0.03;
        $max = 0.80;

        $increments = $max / $affixes->count();

        $values = range($min, $max, $increments);

        foreach ($affixes as $index => $affix) {
            if (isset($values[$index])) {
                $affix->devouring_light = $values[$index];
            } else {
                $affix->devouring_light = $max;
            }

            $affix->save();
        }
    }

    public function reBalanceDamage() {
        $affixes = ItemAffix::where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

        $min = 100;
        $max = 50000;

        $increments = round($max / $affixes->count());

        $values = range($min, $max, $increments);

        foreach ($affixes as $index => $affix) {
            if (isset($values[$index])) {
                $affix->damage = $values[$index];
            } else {
                $affix->damage = $max;
            }

            $affix->save();
        }
    }

    public function reBalanceFightAndMovementTimeOut() {
        $timeouts = [
            'fight_time_out_mod_bonus',
            'move_time_out_mod_bonus',
        ];

        foreach ($timeouts as $field) {

            $affixes = ItemAffix::where($field, '>', 0)->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

            $min = 0.05;
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

    public function reBalanceSkillBonuses() {
        $skills = [
            'Weapon Crafting',
            'Armour Crafting',
            'Spell Crafting',
            'Ring Crafting',
            'Artifact Crafting',
            'Enchanting',
            'Alchemy',
            'Accuracy',
            'Dodge',
            'Looting',
            'Quick Feet',
            'Casting Accuracy',
            'Criticality',
            'Kingmanship',
            'Soldier\'s Strength',
            'Shadow Dance',
            'Blood Lust',
            'Nature\'s Insight',
            'Alchemist\'s Concoctions',
            'Hell\'s Anvil',
            'Celestial Prayer',
            'Astral Magics',
            'Fighter\'s Resilience',
        ];

        foreach ($skills as $skill) {

            $affixes = ItemAffix::where('skill_name', $skill)->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get();

            if ($affixes->count() === 0) {
                continue;
            }

            $min = 0.05;
            $max = 0.50;

            $increments = $max / $affixes->count();

            $values = range($min, $max, $increments);

            foreach ($affixes as $index => $affix) {

                if (isset($values[$index])) {
                    $affix->skill_training_bonus = $values[$index];
                    $affix->skill_bonus          = $values[$index];
                } else {
                    $affix->skill_training_bonus = $max;
                    $affix->skill_bonus          = $max;
                }

                $affix->save();
            }
        }
    }
}
