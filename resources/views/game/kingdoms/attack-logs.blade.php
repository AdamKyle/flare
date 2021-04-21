@extends('layouts.app')

@section('content')
    <div class="justify-content-center">
        <x-core.page-title
            title="Attack Logs"
            route="{{route('game')}}"
            link="Home"
            color="primary"
        ></x-core.page-title>
        <div class="alert alert-info">Attack logs are kept for 7 days before being automatically deleted. You may of course choose to delete them your self.</div>
        @livewire('kingdom.logs.data-table', [
            'attackLogs' => $logs,
            'character'  => $character,
        ])
@endsection
