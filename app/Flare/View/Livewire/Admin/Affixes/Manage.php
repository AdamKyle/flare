<?php

namespace App\Flare\View\Livewire\Admin\Affixes;

use App\Flare\View\Livewire\Core\FormWizard;

class Manage extends FormWizard
{
    public function mount() {
        $this->steps = [
            'Affix Details', 'Affix Modifiers',
        ];

        $this->views = [
            'affix-details', 'affix-modifiers',
        ];

        $this->currentStep = 1;

        $this->finishRoute = 'affixes.list';
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.manage');
    }
}
