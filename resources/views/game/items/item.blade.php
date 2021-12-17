@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 m-auto mt-10 mb-10">
        <x-core.page-title-slot
            route="{{url()->previous()}}"
            link="Back"
            color="success"
        >
            <x-item-display-color :item="$item" />
        </x-core.page-title-slot>
        <hr />
        @if ($item->market_sellable)
            <div class="alert alert-info mt-1 mb-2">This item can be sold on the market.</div>
        @endif

        @include('game.items.partials.item', [
            'item'      => $item,
            'effects'   => $effects,
            'monster'   => $monster,
            'quest'     => $quest,
            'location'  => $location,
            'adventure' => $adventure,
            'skills'    => $skills,
            'skill'     => $skill,
        ])
    </div>
@endsection
