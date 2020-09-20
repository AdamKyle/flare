<?php

namespace App\Flare\View\Livewire\Admin\Monsters\Partials;

use Livewire\Component;

class Monster extends Component
{

    protected $listeners = ['updateCurrentStep'];

    public $currentStep  = 0;
    public $views        = [];
    public $monster      = null;
    
    public function updateCurrentStep(int $currentStep, $monster) {
        $this->monster     = $monster;
        $this->currentStep = $currentStep;
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.partials.monster');
    }
}
