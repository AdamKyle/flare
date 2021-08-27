@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Attack Log ({{$type}})"
        route="{{route('game.kingdom.attack-logs', ['character' => $character])}}"
        link="Back"
        color="primary"
    ></x-core.page-title>

    <x-tabs.pill-tabs-container>
        <x-tabs.tab tab="log" title="Log" selected="true" active="true" />
        <x-tabs.tab tab="enemy" title="Enemy Data" selected="false" active="false" disabled="{{KingdomLogStatus::statusType($type)->tookKingdom() || KingdomLogStatus::statusType($type)->bombsDropped()}}"/>
    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        <x-tabs.tab-content-section tab="log" active="true">
            @if (KingdomLogStatus::statusType($type)->kingdomWasAttacked() || KingdomLogStatus::statusType($type)->bombsDropped())
                @include('game.kingdoms.partials.kingdom-attacked', ['log' => $log, 'lost' => false])
            @elseif (KingdomLogStatus::statusType($type)->attackedKingdom())
                @include('game.kingdoms.partials.attacked-kingdom', ['log' => $log, 'lost' => false])
            @elseif (KingdomLogStatus::statusType($type)->lostAttack())
                @include('game.kingdoms.partials.attacked-kingdom', ['log' => $log, 'lost' => true])
            @elseif(KingdomLogStatus::statusType($type)->tookKingdom())
                @include('game.kingdoms.partials.took-kingdom', ['log' => $log, 'lost' => false])
            @endif
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="enemy">
            @if (!KingdomLogStatus::statusType($type)->tookKingdom())

                @include('game.kingdoms.partials.attacked-kingdom-defender-data', ['log' => $log])
            @endif
        </x-tabs.tab-content-section>
    </x-tabs.tab-content>


@endsection
