@php
    $backUrl = route('items.list');

    if (!auth()->user()->hasRole('Admin')) {
        $backUrl = route('game.shop.buy', ['character' => auth()->user()->character->id]);
    }
@endphp

<x-core.page-title
    title="{{$item->name}}"
    route="{{$backUrl}}"
    color="success" link="Back"
>
    @if (auth()->user()->hasRole('Admin'))
        <x-core.buttons.link-buttons.primary-button
            href="route('items.edit', ['item' => $item->id])}}"
            css="tw-ml-2"
        >
            Edit Item
        </x-core.buttons.link-buttons.primary-button>
    @endif
</x-core.page-title>

<div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

@include('game.items.components.item-details')
