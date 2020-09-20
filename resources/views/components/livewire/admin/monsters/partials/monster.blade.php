<div>
    @if (isset($views[$currentStep - 1])) 
        @switch ($views[$currentStep - 1])
            @case ('stats')
                @livewire('admin.monsters.partials.stats', [
                    'monster' => $monster,
                ])
                @break
            @case('skills')
                @livewire('admin.monsters.partials.skills', [
                    'monster' => $monster,
                ])
                @break
            @case('quest-item')
                @livewire('admin.monsters.partials.quest-item', [
                    'monster' => $monster,
                ])
                @break;
            @default
                @break;
        @endswitch
    @endif
</div>
