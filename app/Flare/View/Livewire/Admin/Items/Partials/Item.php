<?php

namespace App\Flare\View\Livewire\Admin\Items\Partials;

use Livewire\Component;

class Item extends Component
{
    protected $listeners = ['updateCurrentStep'];

    public $currentStep  = 0;
    public $views        = [];
    public $item         = null;
    
    public function updateCurrentStep(int $currentStep, $item) {
        $this->item        = $item;
        $this->currentStep = $currentStep;
    }
    
    public function render()
    {
        return view('components.livewire.admin.items.partials.item');
    }
}
