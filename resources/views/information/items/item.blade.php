@extends('layouts.information')

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        <x-core.page-title-slot
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        >
            <x-item-display-color :item="$item" />
        </x-core.page-title-slot>
        <hr />
        @if ($item->market_sellable)
            <x-core.alerts.info-alert title="Market Info">This item can be sold on the market.</x-core.alerts.info-alert>
        @endif
        @include('game.items.partials.item', [
            'item' => $item
        ])
    </div>
@endsection
