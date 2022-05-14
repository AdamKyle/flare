<?php

namespace App\Flare\View\Livewire\Admin\Skills;

use App\Flare\Models\GameSkill;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SkillsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return GameSkill::query()->when($this->getAppliedFilterWithValue('types'), function ($query, $type) {


        });
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options([
                    ''               => 'Please Select',
                    'class_specific' => 'Class Specific',
                    'can_train'      => 'Can Train',
                    'cannot_train'   => 'Cannot Train',
                ])->filter(function(Builder $builder, string $value) {
                    switch($value) {
                        case 'class_specific':
                            return $builder->whereNotNull('game_class_id');
                        case 'can_train':
                            return $builder->where('can_train', true);
                        case 'cannot_train':
                            return $builder->where('can_train', false);
                        default:
                            return $builder;
                    }
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id')->hideIf(true),
            Column::make('Name')->searchable()->format(function($value, $row) {
                return '<a href="/admin/skill/'.$row->id.'">'.$value.'</a>';
            })->html(),
            Column::make('Trainable?', 'can_train')->sortable()->format(function($value, $row) {
                return $row->can_train ? 'Yes' : 'No';
            })->html(),
            Column::make('Game Class', 'game_class_id')->sortable()->format(function($value, $row) {
                if (!is_null($row->game_class_id)) {
                    return '<a href="/admin/classes/'.$row->game_class_id.'">'.$row->gameClass->name.'</a>';
                }

                return 'N/A';
            })->html(),
            Column::make('Is Locked?', 'is_locked')->sortable()->format(function($value, $row) {
                return $row->is_locked ? 'Yes' : 'No';
            })->html(),
            Column::make('Skill Type', 'type')->sortable()->format(function($value, $row) {
                if (!is_null($row->type)) {
                    return $row->skillType()->getNamedValue();
                }

                return 'N/A';
            })->html(),
        ];
    }
}
