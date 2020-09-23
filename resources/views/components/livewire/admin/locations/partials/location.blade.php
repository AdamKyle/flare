<div>
    @if (isset($views[$currentStep - 1])) 
        @switch ($views[$currentStep - 1])
            @case ('details')
                @livewire('admin.locations.partials.details', [
                    'location' => $location,
                ])
                @break
            @case('quest-item')
                @livewire('admin.locations.partials.quest-item', [
                    'location' => $location,
                ])
                @break;
            @default
                @break;
        @endswitch
    @endif
</div>
