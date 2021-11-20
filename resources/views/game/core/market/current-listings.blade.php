@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
            title="My Listings"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>
    </div>
    @livewire('market.current-listings', [
        'character' => $character
    ])
@endsection
