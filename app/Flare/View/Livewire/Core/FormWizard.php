<?php

namespace App\Flare\View\Livewire\Core;

use App\Flare\Models\Monster;
use Livewire\Component;

class FormWizard extends Component
{

    public $steps           = [];

    public $currentStep     = 0;

    public $views           = [];

    public $modelName       = null;

    public $model           = null;

    public $viewData        = [];

    public $finishRoute     = '';

    public $flasMessageType = '';

    public $flashMessage    = '';

    public $editing         = false;

    protected $listeners    = ['storeModel', 'nextStep', 'finish', 'sessionMessage'];

    public function nextStep(int $index, bool $passed = false) {
        if (isset($this->views[$index - 1]) && !$passed) {
            $this->emitTo($this->views[$index - 1], 'validateInput', 'nextStep', $index);
        }

        if ($passed) {
            $this->currentStep = $index;

            $this->emitTo($this->views[$index], 'update', $this->model['id']);
        }
    }

    public function previousStep($index) {
        $this->currentStep = $index;
    }

    public function finish(int $index, bool $passed = false, $sessionMessage = []) {
        if (isset($this->views[$index]) && !$passed) {
            $this->emitTo($this->views[$index], 'validateInput', 'finish', $index);
        }

        if ($passed) {

            if (!empty($sessionMessage)) {
                session()->flash($sessionMessage['type'], $sessionMessage['message']);
            }


            redirect()->route($this->finishRoute);
        }
    }

    public function sessionMessage(string $type, string $message) {
        $this->flasMessageType = $type;
        $this->flashMessage    = $message;
    }

    public function storeModel($model = null, $refresh = false, $view = null) {
        $this->model = $model;

        if ($refresh) {
            $this->emitTo($view, 'update', $this->model['id']);
        }
    }

    public function mount() {
        $this->viewData[$this->modelName] = $this->model;
        $this->viewData['editing']        = $this->editing;
    }

    public function render()
    {
        return view('components.livewire.core.form-wizard');
    }
}
