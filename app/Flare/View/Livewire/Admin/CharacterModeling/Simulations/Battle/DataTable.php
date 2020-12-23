<?php

namespace App\Flare\View\Livewire\Admin\CharacterModeling\Simulations\Battle;

use App\Flare\Models\CharacterSnapShot;
use Livewire\Component;

class DataTable extends Component
{

    public $monster;

    public $perPage = 10;

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        return CharacterSnapShot::where('battle_simmulation_data->monster_id', $this->monster->id);
    }

    public function render()
    {
        return view('components.livewire.admin.character-modeling.simulations.battle.data-table', [
            'data' => $this->data,
        ]);
    }
}
