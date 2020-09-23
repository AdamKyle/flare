<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use Livewire\Component;
use App\Flare\Models\Monster;
use App\Flare\View\Livewire\Core\FormWizard;

class Create extends FormWizard
{
    public function mount() {
        $this->steps = [
            'Monster', 'Monster Skills (Optional)', 'Monster Quest Rewards (Optional)'
        ];

        $this->views = [
            'stats', 'skills', 'quest-item'
        ];

        $this->currentStep = 1;

        $this->finishRoute = 'monsters.list';
    }

    public function render() {
        return view('components.livewire.admin.monsters.create');
    }
}
