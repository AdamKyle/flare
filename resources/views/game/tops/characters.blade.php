@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 ml-auto mr-auto">
        <x-core.page-title
            title="Characters"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        @livewire('game.tops.characters')
    </div>
@endsection
