@php
    $backUrl = route('items.list');

    if (is_null(auth()->user())) {
        $backUrl = url()->previous();
    } else {
        if (!auth()->user()->hasRole('Admin')) {
            $backUrl = route('game.shop.buy', ['character' => auth()->user()->character->id]);
        }
    }


@endphp

<h2 class="mt-2 font-light">
    <x-item-display-color :item="$item" />
</h2>

<div class="relative">
    <div class="float-right mt-[-30px]">
        @auth
            @if (auth()->user()->hasRole('Admin'))
                <x-core.buttons.link-buttons.success-button
                    href="{{$backUrl}}"
                    css="tw-ml-2"
                >
                    Back
                </x-core.buttons.link-buttons.success-button>
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('items.edit', ['item' => $item->id])}}"
                    css="tw-ml-2"
                >
                    Edit Item
                </x-core.buttons.link-buttons.primary-button>
            @endif
        @endauth
    </div>
</div>

<div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

@if ($item->type === 'alchemy' && $item->damages_kingdoms)
    @include('game.items.components.item-kingdom-details')
@elseif ($item->type === 'alchemy' && !is_null($item->holy_level))
    @include('game.items.components.item-holy-oil-details')
@elseif ($item->type === 'alchemy' && $item->usable)
    @include('game.items.components.item-usable')
@else
    @include('game.items.components.item-details')
@endif
