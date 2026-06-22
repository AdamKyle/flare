@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Rolled Stats: {{ $gameLocationGemParamter->name }}"
            buttons="true"
            :back-url="route('admin.location-gems.show', ['gameLocationGemParamter' => $gameLocationGemParamter])"
        >
            @include('admin.gems.partials.rolled-details', [
                'rolledGem' => $gameLocationGemParamter->rolledGem,
                'rollCount' => $gameLocationGemParamter->roll_count,
                'showCharacterPowerReduction' => false,
            ])
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
