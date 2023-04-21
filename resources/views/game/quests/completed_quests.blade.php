@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Completed Quests"
            route="{{route('game')}}"
            color="primary" link="Game"
        >
        </x-core.page-title>

        @livewire('character.completed-quests.completed-quests')
    </x-core.layout.info-container>
@endsection
