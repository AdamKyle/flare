<?php

namespace App\Flare\View\Livewire\Admin\CharacterModeling\Simulations\Adventure;

use App\Flare\Models\CharacterSnapShot;
use Livewire\Component;

class DataTable extends Component
{
    public $adventure;

    public $perPage = 10;

    public function getDataProperty() {
        return $this->dataQuery->paginate($this->perPage);
    }

    public function getDataQueryProperty() {
        return CharacterSnapShot::where('adventure_simmulation_data->adventure_id', $this->adventure->id);
    }

    public function render()
    {
        return view('components.livewire.admin.character-modeling.simulations.adventure.data-table', [
            'data' => $this->data,
        ]);
    }
}
