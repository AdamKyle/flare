<?php

namespace App\Flare\View\Livewire\Admin\Affixes;

use App\Flare\Models\ItemAffix;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AffixesTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return ItemAffix::where('randomly_generated', false);
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function(Builder $builder, string $value) {
                    $invalidField = '0';
                    $damageField  = 'damage';
                    $booleanFields = ['irresistible_damage', 'damage_can_stack'];

                    if ($invalidField === $value) {
                        return $builder;
                    }

                    if ($damageField === $value) {
                        return $builder->where($value, '>', 0);
                    }

                    if (in_array($value, $booleanFields)) {
                        return $builder->where($value, true);
                    }

                    if (preg_match('/_/', $value)) {
                        return $builder->where($value, '>', 0);
                    }

                    return $builder->where('skill_name', $value);
                }),
        ];
    }

    protected function buildOptions(): array {
        return [
            '0'                        => 'Please Select',
            'str_mod'                  => 'Raises STR',
            'int_mod'                  => 'Raises Int',
            'dex_mod'                  => 'Raises Dex',
            'chr_mod'                  => 'Raises Chr',
            'agi_mod'                  => 'Raises Agi',
            'focus_mod'                => 'Raises Focus',
            'dur_mod'                  => 'Raises Dur',
            'str_reduction'            => 'Str Reduction',
            'dur_reduction'            => 'Dur Reduction',
            'dex_reduction'            => 'Dex Reduction',
            'chr_reduction'            => 'Chr Reduction',
            'int_reduction'            => 'Int Reduction',
            'agi_reduction'            => 'Agi Reduction',
            'focus_reduction'          => 'Focus Reduction',
            'class_bonus'              => 'Class Bonus Modifier',
            'base_damage_mod'          => 'Base Damage',
            'base_ac_mod'              => 'Base AC',
            'base_healing_mod'         => 'Base Healing',
            'skill_training_bonus'     => 'Skill Training Bonus',
            'skill_bonus'              => 'Skill Bonus',
            'skill_reduction'          => 'Enemy Skill Reduction',
            'resistance_reduction'     => 'Resistance Reduction',
            'base_damage_mod_bonus'    => 'Skill Base Damage',
            'base_healing_mod_bonus'   => 'Skill Healing',
            'base_ac_mod_bonus'        => 'Skill AC',
            'fight_time_out_mod_bonus' => 'Fight Timeout Modifiers',
            'move_time_out_mod_bonus'  => 'Move Timeout Modifiers',
            'devouring_light'          => 'Devouring Light',
            'entranced_chance'         => 'Entrances Enemy',
            'steal_life_amount'        => 'Steal Life',
            'damage'                   => 'Damage',
            'irresistible_damage'      => 'Irresistible Damage',
            'damage_can_stack'         => 'Stacking Damage',
            'Weapon Crafting'          => 'Weapon Crafting',
            'Armour Crafting'          => 'Armour Crafting',
            'Spell Crafting'           => 'Spell Crafting',
            'Ring Crafting'            => 'Ring Crafting',
            'Enchanting'               => 'Enchanting',
            'Alchemy'                  => 'Alchemy',
            'Accuracy'                 => 'Accuracy',
            'Dodge'                    => 'Dodge',
            'Looting'                  => 'Looting',
            'Quick Feet'               => 'Quick Feet',
            'Casting Accuracy'         => 'Casting Accuracy',
            'Criticality'              => 'Criticality',
            'Soldier\'s Strength'      => 'Soldier\'s Strength',
            'Shadow Dance'             => 'Shadow Dance',
            'Blood Lust'               => 'Blood Lust',
            'Nature\'s Insight'        => 'Nature\'s Insight',
            'Alchemist\'s Concoctions' => 'Alchemist\'s Concoctions',
            'Hell\'s Anvil'            => 'Hell\'s Anvil',
            'Celestial Prayer'         => 'Celestial Prayer',
            'Astral Magics'            => 'Astral Magics',
            'Fighter\'s Resilience'    => 'Fighter\'s Resilience',
            'Incarcerated Thoughts'    => 'Incarcerated Thoughts'
        ];
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $affixId = ItemAffix::where('name', $value)->first()->id;

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/affixes/'. $affixId.'">'.$row->name . '</a>';
                    }
                }

                return '<a href="/information/affix/'. $affixId.'" target="_blank">  <i class="fas fa-external-link-alt"></i> '.$row->name . '</a>';
            })->html(),

            Column::make('Type')->searchable(),

            Column::make('Damage Mod', 'base_damage_mod')->sortable()->format(function ($value) {
                return ($value * 100) . '%';
            }),
            Column::make('AC Mod', 'base_ac_mod')->sortable()->format(function ($value) {
                return ($value * 100) . '%';
            }),
            Column::make('Healing Mod', 'base_healing_mod')->sortable()->format(function ($value) {
                return ($value * 100) . '%';
            }),
            Column::make('Int Required', 'int_required')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Min Enchanting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Enchanting Lv.', 'skill_level_trivial')->sortable(),
        ];
    }
}
