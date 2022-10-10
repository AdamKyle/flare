@props([
    'title'       => 'Example',
    'route'       => null,
    'css'         => '',
    'buttons'     => 'false',
    'backUrl'     => '#',
    'editUrl'     => '#',
])

<div class="mb-4">
    @if ($buttons != 'false')
        <div class="flex items-center relative">
            @if (!is_null($route))
                <h2 class="font-light mb-3">
                    <a href={{$route}} {{$attributes}}>{!!  $title !!}</a>
                </h2>
            @else
                <h2 class="font-light mb-3">{!!  $title !!}</h2>
            @endif
            <div class="absolute right-0 top-[8px]">
                @if (!is_null(auth()->user()))
                    @if (auth()->user()->hasRole('Admin') && $editUrl !== '#')
                        <x-core.buttons.link-buttons.primary-button href="{{$editUrl}}">
                            Edit
                        </x-core.buttons.link-buttons.primary-button>
                    @endif
                @endif
                <x-core.buttons.link-buttons.success-button href="{{$backUrl}}">
                    Back
                </x-core.buttons.link-buttons.success-button>
            </div>
        </div>
    @else
        @if (!is_null($route))
            <h2 class="font-light mb-3">
                <a href={{$route}} {{$attributes}}>{{$title}}</a>
            </h2>
        @else
            <h2 class="font-light mb-3">{{$title}}</h2>
        @endif
    @endif



    <div class="bg-white rounded-md drop-shadow-sm p-6 overflow-x-auto dark:bg-gray-800">
        {{$slot}}
    </div>
</div>
