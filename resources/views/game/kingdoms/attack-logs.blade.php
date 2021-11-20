@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
            title="Attack Logs"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>
        <x-core.alerts.info-alert>
            <p>
                Attack logs are kept for 7 days before being automatically deleted. This table is not updated in real time. Check your unit movement to see when any units might return.
            </p>
        </x-core.alerts.info-alert>
    </div>

    @livewire('kingdom.logs.data-table', [
        'attackLogs' => $logs,
        'character'  => $character,
    ])
@endsection
