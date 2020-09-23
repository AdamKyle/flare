<?php

namespace App\Flare\View\Livewire\Admin\Locations\Partials;

use Livewire\Component;

class Location extends Component
{
    protected $listeners = ['updateCurrentStep'];

    public $currentStep  = 0;
    public $views        = [];
    public $location     = null;
    
    public function updateCurrentStep(int $currentStep, $location) {
        $this->location    = $location;
        $this->currentStep = $currentStep;
    }
    
    public function render()
    {
        return view('components.livewire.admin.locations.partials.location');
    }
}
