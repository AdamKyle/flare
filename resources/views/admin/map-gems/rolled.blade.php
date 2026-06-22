@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Rolled Stats: {{ $gameMapGemParamter->name }}"
            buttons="true"
            :back-url="route('admin.map-gems.show', ['gameMapGemParamter' => $gameMapGemParamter])"
        >
            @include('admin.gems.partials.rolled-details', [
                'rolledGem' => $gameMapGemParamter->rolledGem,
                'rollCount' => $gameMapGemParamter->roll_count,
                'showCharacterPowerReduction' => true,
            ])
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
