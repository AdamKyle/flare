@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Attack Log"
        route="{{route('game.kingdom.attack-logs', ['character' => $character])}}"
        link="Back"
        color="primary"
    ></x-core.page-title>
    <x-cards.card>
        @if (KingdomLogStatus::statusType($type)->kingdomWasAttacked())
            @include('game.kingdoms.partials.kingdom-attacked', ['log' => $log])
        @elseif (KingdomLogStatus::statusType($type)->attackedKingdom())
            @include('game.kingdoms.partials.attacked-kingdom', ['log' => $log])
        @endif
    </x-cards.card>
@endsection
