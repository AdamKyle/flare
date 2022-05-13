<?php

namespace App\Flare\View\Livewire\Admin\PassiveSkills;

use App\Flare\Models\PassiveSkill;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PassiveSkillTable extends DataTableComponent {

    public int $skillId = 0;

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function columns(): array {
        return [
            Column::make('Name')->format(function ($value, $row) {
                $passiveSkillId = PassiveSkill::where('name', $value)->first()->id;

                if (auth()->user()->hasRole('Admin')) {
                    return '<a href="/admin/passive-skill/'. $passiveSkillId.'">'.$row->name . '</a>';
                }

                return '<a href="/information/passive-skill/'. $passiveSkillId.'" >'.$row->name . '</a>';
            })->html(),
            Column::make('Max Level', 'max_level'),
            Column::make('Unlocks at Level', 'unlocks_at_level'),
            Column::make('Parent', 'parent_skill_id')->format(function ($value, $row) {
                if (is_null($row->parent_id)) {
                    return 'Is Parent';
                }

                if (auth()->user()->hasRole('Admin')) {
                    return '<a href="/admin/passive-skill/'. $row->parent_skill_id.'">'.$row->parent->name . '</a>';
                }

                return '<a href="/information/passive-skill/'. $row->parent_skill_id.'" >'.$row->parent->name . '</a>';
            })->html(),
            Column::make('Unlocks', 'parent_skill_id')->format(function ($value, $row) {
                $passive = PassiveSkill::where('name', $row->name)->first();

                if ($passive->childSkills->isEmpty()) {
                    return 'Has no unlockables';
                }

                $passives = $passive->childSkills->pluck('name', 'id')->toArray();

                $length       = count($passives);
                $currentIndex = 0;
                $links        = '';

                foreach ($passives as $id => $name) {
                    $currentIndex++;

                    if ($currentIndex !== $length) {
                        if (auth()->user()->hasRole('Admin')) {
                            $links .= '<a href="/admin/passive-skill/'. $id.'">'.$name . '</a>, ';
                        } else {
                            $links .= '<a href="/information/passive-skill/'. $id.'" >'.$name . '</a>, ';
                        }
                    } else {
                        if (auth()->user()->hasRole('Admin')) {
                            $links .= '<a href="/admin/passive-skill/'. $id.'">'.$name . '</a>';
                        } else {
                            $links .= '<a href="/information/passive-skill/'. $id.'" >'.$name . '</a>';
                        }
                    }
                };

                return $links;
            })->html(),
        ];
    }

    public function builder(): Builder {
        if ($this->skillId !== 0) {
            return PassiveSkill::where('parent_skill_id', $this->skillId);
        }

        return PassiveSkill::query();
    }
}
