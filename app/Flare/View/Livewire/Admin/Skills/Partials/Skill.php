<?php

namespace App\Flare\View\Livewire\Admin\Skills\Partials;

use Livewire\Component;

class Skill extends Component
{
    protected $listeners = ['updateCurrentStep'];

    public $currentStep  = 0;
    public $views        = [];
    public $skill        = null;

    public function updateCurrentStep(int $currentStep, $skill) {
        $this->skill       = $skill;
        $this->currentStep = $currentStep;
    }

    public function render()
    {
        return view('components.livewire.admin.skills.partials.skill');
    }
}
