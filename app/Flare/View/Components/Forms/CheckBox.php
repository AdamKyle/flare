<?php

namespace App\Flare\View\Components\Forms;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class CheckBox extends Component
{

    /**
     * @var mixed $item
     */
    public $model;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $label
     */
    public $label;

    /**
     * @var string $modelKey
     */
    public $modelKey;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Model $model, string $name, string $label, string $modelKey)
    {
        $this->model    = $model;
        $this->name     = $name;
        $this->label    = $label;
        $this->modelKey = $modelKey;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     * @throws \Exception
     */
    public function render()
    {
        if (!is_null($this->model)) {
            if (!isset($this->model->{$this->modelKey})) {
                throw new \Exception('Model key: ' . $this->modelKey . ' does not exist on supplied model');
            }
        }

        return view('components.core.forms.check-box');
    }
}
