<div>
    @if (isset($views[$currentStep - 1])) 
        @switch ($views[$currentStep - 1])
            @case ('affix-details')
                @livewire('admin.affixes.partials.affix-details', [
                    'itemAffix' => $itemAffix,
                ])
                @break
            @case('affix-modifiers')
                @livewire('admin.affixes.partials.affix-modifier', [
                    'itemAffix' => $itemAffix,
                ])
                @break;
            @default
                @break;
        @endswitch
    @endif
</div>
