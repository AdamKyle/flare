@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
            title="Previous Adventures"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>
    </div>
    @livewire('character.adventures.data-table', [
        'adventureLogs' => $logs,
        'character'     => $character,
    ])

@endsection
