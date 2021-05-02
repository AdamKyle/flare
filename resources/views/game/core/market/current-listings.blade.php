@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="My Listings"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>
    <div class="row">
        <div class="col-md-12">
            @livewire('market.current-listings', [
                'character' => $character
            ])
        </div>
    </div>
@endsection
