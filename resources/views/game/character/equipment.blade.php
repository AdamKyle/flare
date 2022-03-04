@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Item Comparison"
        route="{{url()->previous()}}"
        link="Back"
        color="success"
    ></x-core.page-title>
    <hr />
    @if ($setEquipped && !$isShop)
        <div class="alert alert-warning mt-2 mb-3">
            Equipping this item while <strong>Set {{$setIndex}}</strong> is equipped will replace the set item and equip this item.
            The set item will be placed into your inventory assuming you have the space. If not, you will be redirected back and told make some room.
        </div>
    @elseif($setEquipped && $isShop)
        <div class="alert alert-warning mt-2 mb-3">
            Replacing this (or one of these) set item with the shop item will <strong>not</strong> unequip the set
            and instead <strong>replace in the position stated you selected</strong>. The item replaced will be put into your inventory.
        </div>
    @elseif($isShop)
        <div class="alert alert-warning mt-2 mb-3">
            Replacing this (or one of these) items will replace the item in the position stated.
            Should you have no item equipped, this will just be equipped.s
        </div>
    @endif
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            @if (empty($details))
                <div class="alert alert-info">
                    You have nothing equipped for this item type. Anything is better than nothing.
                </div>
            @else
                @foreach($details as $key => $value)
                    @include('game.character.partials.equipment.equipped', ['equipment' => $details[$key]])
                @endforeach
            @endif
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            @include('game.character.partials.equipment.to-equip', [
                'item'           => $itemToEquip,
                'details'        => $details,
                'slotId'         => $slotId,
                'details'        => $details,
                'itemToEquip'    => $itemToEquip,
                'type'           => $type,
                'bowEquipped'    => $bowEquipped,
                'staveEquipped'  => $staveEquipped,
                'hammerEquipped' => $hammerEquipped,
                'isShop'         => isset($isShop) ? $isShop : false,
            ])
        </div>
    </div>
@endsection
