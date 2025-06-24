@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title
            title="Completed Guide Quests"
            route="{{route('game')}}"
            color="primary"
            link="Game"
        ></x-core.page.title>

        @livewire('character.completed-guide-quests.completed-guide-quests')
    </x-core.layout.info-container>
@endsection
