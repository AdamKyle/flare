@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="My Listings"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        @livewire('market.my-listings')
    </x-core.layout.info-container>
@endsection
