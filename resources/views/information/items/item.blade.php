@extends('layouts.information')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
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
