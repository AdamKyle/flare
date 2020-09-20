<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use App\Flare\Models\Monster;
use Livewire\Component;

class Create extends Component
{

    public $steps = [];

    public $currentStep = 1;

    public $views = [];

    public $monster = null;

    protected $listeners = ['storeMonster', 'nextStep', 'finish'];


    public function mount() {
        $this->steps = [
            'Monster', 'Monster Skills', 'Monster Quest Rewards'
        ];

        $this->views = [
            'stats', 'skills', 'quest-item'
        ];

        $this->currentStep = 1;
    }

    public function nextStep(int $index, bool $passed = false) {
        if (isset($this->views[$index - 1]) && !$passed) {
            $this->emitTo($this->views[$index - 1], 'validateInput', 'nextStep', $index);
        }

        if ($passed) {
            $this->currentStep = $index;
            $this->emit('updateCurrentStep', $this->currentStep, $this->monster);
        }
    }

    public function finish(int $index, bool $passed = false) {
        if (isset($this->views[$index - 1]) && !$passed) {
            $this->emitTo($this->views[$index - 1], 'validateInput', 'finish', $index);
        }

        if ($passed) {
            redirect()->route('monsters.list');
        }
    }

    public function storeMonster($monster = null) {
        $this->monster = $monster;
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.create');
    }
}
