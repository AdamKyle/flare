<?php

namespace App\Flare\View\Components;

use App\Flare\Models\Item;
use Illuminate\View\Component;
use Illuminate\View\View;

class ItemDisplayColor extends Component
{
    public Item $item;

    public string $color;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string|View
    {
        $isEitherRandomlyGenerated = $this->isEitherEnchantRandomlyGenerated();

        if ($isEitherRandomlyGenerated) {
            if ($this->item->is_cosmic) {
                $this->color = 'cosmic';
            } elseif ($this->item->is_mythic) {
                $this->color = 'mythic';
            } else {
                $this->color = 'unique-item';
            }
        } else {
            if ($this->item->type === 'artifact') {
                $this->color = 'artifact';
            } elseif ($this->item->type === 'trinket') {
                $this->color = 'trinket';
            } elseif ($this->item->appliedHolyStacks->isNotEmpty()) {
                $this->color = 'holy-item';
            } elseif (! is_null($this->item->itemSuffix) && ! is_null($this->item->itemPrefix)) {
                $this->color = 'two-enchant';
            } elseif (! is_null($this->item->itemSuffix) || ! is_null($this->item->itemPrefix)) {
                $this->color = 'one-enchant';
            } elseif ($this->item->type === 'quest') {
                $this->color = 'quest-item';
            } elseif ($this->item->usable || $this->item->can_use_on_other_items) {
                $this->color = 'usable-item';
            } else {
                $this->color = 'normal-item';
            }
        }

        return view('components.item-display-color');
    }

    protected function isEitherEnchantRandomlyGenerated(): bool
    {
        if (! is_null($this->item->itemSuffix)) {
            return $this->item->itemSuffix->randomly_generated;
        }

        if (! is_null($this->item->itemPrefix)) {
            return $this->item->itemPrefix->randomly_generated;
        }

        return false;
    }
}
