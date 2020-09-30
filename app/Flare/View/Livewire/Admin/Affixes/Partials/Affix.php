<?php

namespace App\Flare\View\Livewire\Admin\Affixes\Partials;

use Livewire\Component;

class Affix extends Component
{
    protected $listeners = ['updateCurrentStep'];

    public $currentStep  = 0;
    public $views        = [];
    public $itemAffix    = null;
    
    public function updateCurrentStep(int $currentStep, $itemAffix) {
        $this->itemAffix   = $itemAffix;
        $this->currentStep = $currentStep;
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.partials.affix');
    }
}
