@props([
    'title' => '',
    'route' => '#',
    'color' => 'primary',
    'link' => '',
])

<x-core.grids.two-column>
    <x-slot name="columnOne">
        <h1 class="mt-2 font-light text-break text-2xl text-gray-800 dark:text-gray-400">{{$title}}</h1>
    </x-slot>
    <x-slot name="columnTwo">
        <div class="relative">
            <div class="float-right mt-[14px]">
                @switch($color)
                    @case('success')
                        <x-core.buttons.link-buttons.success-button
                            href="{{$route}}"
                        >
                            {{ $link }}
                        </x-core.buttons.link-buttons.success-button>

                        @break
                    @case('primary')
                        <x-core.buttons.link-buttons.primary-button
                            href="{{$route}}"
                        >
                            {{ $link }}
                        </x-core.buttons.link-buttons.primary-button>

                        @break
                    @default
                        <x-core.buttons.link-buttons.success-button
                            href="{{$route}}"
                        >
                            {{ $link }}
                        </x-core.buttons.link-buttons.success-button>
                @endswitch

                {{ $slot }}
            </div>
        </div>
    </x-slot>
</x-core.grids.two-column>
