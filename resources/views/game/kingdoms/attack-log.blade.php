@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Attack Log ({{$type}})"
        route="{{route('game.kingdom.attack-logs', ['character' => $character])}}"
        link="Back"
        color="primary"
    ></x-core.page-title>

    @if (KingdomLogStatus::statusType($type)->kingdomWasAttacked())
        @include('game.kingdoms.partials.kingdom-attacked', ['log' => $log, 'lost' => false])
    @elseif (KingdomLogStatus::statusType($type)->attackedKingdom())
        @include('game.kingdoms.partials.attacked-kingdom', ['log' => $log, 'lost' => false])
    @elseif (KingdomLogStatus::statusType($type)->lostAttack())
        @include('game.kingdoms.partials.attacked-kingdom', ['log' => $log, 'lost' => true])
    @elseif(KingdomLogStatus::statusType($type)->tookKingdom())
        @include('game.kingdoms.partials.took-kingdom', ['log' => $log, 'lost' => false])
    @endif
@endsection
