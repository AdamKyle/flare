@extends('layouts.app')

@section('content')
    <x-core.cards.card-with-title
        title="Upcoming events!"
        buttons="true"
        backUrl="{{route('game')}}"
    >
        <div id="player-event-calendar" data-in-game="true"></div>
    </x-core.cards.card-with-title>
@endSection

@push('scripts')
    @vite('resources/js/player-event-calendar-component.ts')
@endpush
