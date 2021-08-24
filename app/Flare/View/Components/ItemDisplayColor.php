<?php

namespace App\Flare\View\Components;

use App\Flare\Models\Item;
use Illuminate\View\Component;

class ItemDisplayColor extends Component
{

    /**
     * @var mixed $item
     */
    public $item;

    /**
     * @var string
     */
    public $color;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        if (!is_null($this->item->itemSuffix) && !is_null($this->item->itemPrefix)) {
            $this->color = 'two-enchant';
        } else if (!is_null($this->item->itemSuffix) || !is_null($this->item->itemPrefix)) {
            $this->color = 'one-enchant';
        } elseif ($this->item->type === 'quest') {
            $this->color = 'quest-item';
        } elseif ($this->item->usable) {
            $this->color = 'usable-item';
        } else {
            $this->color = 'normal-item';
        }

        return view('components.item-display-color');
    }
}
