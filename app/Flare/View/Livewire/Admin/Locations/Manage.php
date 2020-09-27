<?php

namespace App\Flare\View\Livewire\Admin\Locations;

use App\Flare\View\Livewire\Core\FormWizard;

class Manage extends FormWizard
{
    public function mount() {
        $this->steps = [
            'Location', 'Quest Reward (Optional)'
        ];

        $this->views = [
            'details', 'quest-item'
        ];

        $this->currentStep = 1;

        $this->finishRoute = 'locations.list';
    }

    public function render()
    {
        return view('components.livewire.admin.locations.manage');
    }
}
