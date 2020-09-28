<?php

namespace App\Flare\View\Livewire\Admin\Items;

use App\Flare\View\Livewire\Core\FormWizard;

class Manage extends FormWizard
{
    
    public function mount() {
        $this->steps = [
            'Item Details', 'Item Modifiers',
        ];

        $this->views = [
            'item-details', 'item-modifiers',
        ];

        $this->currentStep = 1;

        $this->finishRoute = 'items.list';
    }

    public function render()
    {
        return view('components.livewire.admin.items.manage');
    }
}
