@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
            route="{{route('game')}}"
            link="Game"
            color="primary"
            title="Completed Quests"
        ></x-core.page-title>

        @livewire('character.completed-quests.data-table', [
            'character' => $character,
        ])
    </div>
@endsection
