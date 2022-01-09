@props([
    'route' => '#',
    'color' => 'primary',
    'link'  => ''
])

<x-core.grids.two-column>
    <x-slot name="columnOne">
        <h2 class="font-light">{{$slot}}</h2>
    </x-slot>
    <x-slot name="columnTwo">
      <div class="tw-float-right">
        @switch($color)
            @case('success')
                <x-core.buttons.link-buttons.success-button href="{{$route}}">
                    {{$link}}
                </x-core.buttons.link-buttons.success-button>
                @break
            @case('primary')
                <x-core.buttons.link-buttons.primary-button href="{{$route}}">
                    {{$link}}
                </x-core.buttons.link-buttons.primary-button>
                @break
            @default
                <x-core.buttons.link-buttons.success-button href="{{$route}}">
                    {{$link}}
                </x-core.buttons.link-buttons.success-button>
        @endswitch
      </div>
    </x-slot>
</x-core.grids.two-column>