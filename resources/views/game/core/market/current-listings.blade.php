@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="My Listings"
            route="{{route('game.market')}}"
            link="Market Board"
            color="success"
        ></x-core.page-title>

        @livewire('market.my-listings')
    </x-core.layout.info-container>
@endsection
