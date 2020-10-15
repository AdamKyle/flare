<?php

namespace App\Flare\View\Livewire\Admin\Skills;

use App\Flare\View\Livewire\Core\FormWizard;

class Manage extends FormWizard
{
    public function mount() {
        $this->steps = [
            'Skill Details', 'Skill Modifiers',
        ];

        $this->views = [
            'skill-details', 'skill-modifiers',
        ];

        $this->currentStep = 1;

        $this->finishRoute = 'skills.list';
    }

    public function render()
    {
        return view('components.livewire.admin.skills.manage');
    }
}
