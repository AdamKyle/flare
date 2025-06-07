@extends('layouts.app')

@section('content')
    <x-core.cards.card-with-title
        title="Upcoming events!"
        buttons="true"
        backUrl="{{route('welcome')}}"
    >
        <div id="player-event-calendar" data-in-game="false"></div>
    </x-core.cards.card-with-title>
@endsection

@push('scripts')
    @vite('resources/js/player-event-calendar-component.ts')
@endpush
