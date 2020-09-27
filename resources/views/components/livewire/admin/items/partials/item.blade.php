<div>
    @if (isset($views[$currentStep - 1])) 
        @switch ($views[$currentStep - 1])
            @case ('item-details')
                @livewire('admin.items.partials.item-details', [
                    'item' => $item,
                ])
                @break
            @case('item-modifiers')
                @livewire('admin.items.partials.item-modifiers', [
                    'item' => $item,
                ])
                @break;
            @case('item-affixes')
                @livewire('admin.items.partials.item-affixes', [
                    'item' => $item,
                ])
                @break;
            @default
                @break;
        @endswitch
    @endif
</div>
