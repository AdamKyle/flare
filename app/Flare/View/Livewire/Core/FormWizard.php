<?php

namespace App\Flare\View\Livewire\Core;

use App\Flare\Models\Monster;
use Livewire\Component;

class FormWizard extends Component
{

    public $steps        = [];

    public $currentStep  = 1;

    public $views        = [];

    public $model        = null;

    public $finishRoute  = '';

    public $flasMessageType = '';

    public $flashMessage = '';

    protected $listeners = ['storeModel', 'nextStep', 'finish', 'redirectSessionMessage'];

    public function nextStep(int $index, bool $passed = false) {
        if (isset($this->views[$index - 1]) && !$passed) {
            $this->emitTo($this->views[$index - 1], 'validateInput', 'nextStep', $index);
        }

        if ($passed) {
            $this->currentStep = $index;
            $this->emit('updateCurrentStep', $this->currentStep, $this->model);
        }
    }

    public function finish(int $index, bool $passed = false) {
        if (isset($this->views[$index - 1]) && !$passed) {
            $this->emitTo($this->views[$index - 1], 'validateInput', 'finish', $index);
        }

        if ($passed) {
            session()->flash($this->flasMessageType, $this->flashMessage);

            redirect()->route($this->finishRoute);
        }
    }

    public function redirectSessionMessage(string $type, string $message) {
        $this->flasMessageType = $type;
        $this->flashMessage    = $message;
    }

    public function storeModel($model = null) {
        $this->model = $model;
    }

    public function render()
    {
        return view('components.livewire.core.form-wizard');
    }
}
