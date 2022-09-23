<?php

namespace App\Flare\View\Livewire\Info\Skills;

use App\Flare\Models\GameSkill;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ClassSkills extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return GameSkill::whereNotNull('game_class_id');
    }

    public function columns(): array
    {
        return [
            Column::make('id')->hideIf(true),
            Column::make('Name')->searchable()->format(function($value, $row) {

                if (is_null(auth()->user())) {
                    return '<a href="/information/skill/'. $row->id.'">'.$row->name .'</a>';
                }

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/skill/'.$row->id.'">'.$value.'</a>';
                    }
                }

                return '<a href="/information/skill/'. $row->id.'">'.$row->name .'</a>';
            })->html(),
            Column::make('Trainable?', 'can_train')->sortable()->format(function($value, $row) {
                return $row->can_train ? 'Yes' : 'No';
            })->html(),
            Column::make('Game Class', 'game_class_id')->sortable()->format(function($value, $row) {
                if (!is_null($row->game_class_id)) {

                    if (is_null(auth()->user())) {
                        return '<a href="/information/class/'. $row->game_class_id.'">'.$row->gameClass->name.'</a>';
                    }

                    if (!is_null(auth()->user())) {
                        if (auth()->user()->hasRole('Admin')) {
                            return '<a href="/admin/classes/'.$row->game_class_id.'">'.$row->gameClass->name.'</a>';
                        }
                    }

                    return '<a href="/information/class/'. $row->game_class_id.'">'.$row->gameClass->name.'</a>';
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
