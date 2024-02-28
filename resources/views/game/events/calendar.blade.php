@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Upcoming Events"
        route="{{route('game')}}"
        link="{{'Game'}}"
        color="{{'primary'}}"
    ></x-core.page-title>
    <hr />
    <div id="player-event-calendar"></div>
@endSection

@push('scripts')
    @vite('resources/js/player-event-calendar-component.ts')
@endpush
