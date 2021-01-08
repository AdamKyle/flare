<?php

namespace App\Flare\View\Livewire\Admin\CharacterModeling\Simulations\Battle;

use Livewire\Component;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithSorting;

    public $monster;

    public $perPage   = 50;

    public $sortField = 'did_die';

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        $snapShots = CharacterSnapShot::where('battle_simmulation_data->monster_id', $this->monster->id);

        $snapShots = $snapShots->get()->transform(function($snapShot) {
            $snapShot->did_die          = $this->didDie($snapShot);
            $snapShot->failed_to_finish = $this->failedToFinish($snapShot);

            return $snapShot;
        });

        $snapShots = $this->sortBy === 'asc' ? $snapShots->sortBy($this->sortField) : $snapShots->sortByDesc($this->sortField);

        return $snapShots;
    }

    public function render()
    {
        return view('components.livewire.admin.character-modeling.simulations.battle.data-table', [
            'data' => $this->data,
        ]);
    }

    protected function didDie(CharacterSnapShot $snapShot) {
        foreach ($snapShot->battle_simmulation_data as $data) {
            if (is_array($data)) {
                if ($data['character_dead'] && !$data['monster_dead']) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function failedToFinish(CharacterSnapShot $snapShot) {
        foreach ($snapShot->battle_simmulation_data as $data) {
            if (is_array($data)) {
                if (!$data['character_dead'] && !$data['monster_dead']) {
                    return true;
                }
            }
        }

        return false;
    }
}
