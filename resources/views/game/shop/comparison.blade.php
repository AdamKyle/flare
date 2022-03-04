@extends('layouts.app')

@section('content')
  @include('game.character.equipment', [
        'isShop'         => true,
        'item'           => $itemToEquip,
        'details'        => $details,
        'slotId'         => $slotId,
        'details'        => $details,
        'itemToEquip'    => $itemToEquip,
        'type'           => $type,
        'bowEquipped'    => $bowEquipped,
        'staveEquipped'  => $staveEquipped,
        'hammerEquipped' => $hammerEquipped,
  ])
@endsection
