<div>
    @if (isset($views[$currentStep - 1])) 
        @switch ($views[$currentStep - 1])
            @case ('skill-details')
                @livewire('admin.skills.partials.skill-details', [
                    'skill' => $skill,
                ])
                @break
            @case('skill-modifiers')
                @livewire('admin.skills.partials.skill-modifiers', [
                    'skill' => $skill,
                ])
                @break;
            @default
                @break;
        @endswitch
    @endif
</div>
