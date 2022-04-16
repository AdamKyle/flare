@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @dump([
            'details'        => $details,
            'slotId'         => $slotId,
            'details'        => $details,
            'itemToEquip'    => $itemToEquip,
            'type'           => $type,
            'bowEquipped'    => $bowEquipped,
            'staveEquipped'  => $staveEquipped,
            'hammerEquipped' => $hammerEquipped,
        ])

        @include('game.shop.components.to-equip', ['item' => $itemToEquip])

        <x-core.cards.card-with-title title="Comparison Data">
            @include('game.shop.components.comparison-details', ['details' => $details])
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
