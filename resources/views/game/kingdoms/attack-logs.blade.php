@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Attack Logs"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>
    <div class="alert alert-info">Attack logs are kept for 7 days before being automatically deleted.</div>
    @livewire('kingdom.logs.data-table', [
        'attackLogs' => $logs,
        'character'  => $character,
    ])
@endsection
