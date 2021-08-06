@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Item Comparison"
        route="{{url()->previous()}}"
        link="Back"
        color="success"
    ></x-core.page-title>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            @if (empty($details))
                <div class="alert alert-info">
                    You have nothing equipped for this item type. Anything is better then nothing.
                </div>
            @else
                @foreach($details as $key => $value)
                    @include('game.character.partials.equipment.equipped', ['equipment' => $details[$key]])
                @endforeach
            @endif
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            @include('game.character.partials.equipment.to-equip', [
                'item'        => $itemToEquip,
                'details'     => $details,
                'slotId'      => $slotId,
                'details'     => $details,
                'itemToEquip' => $itemToEquip,
                'type'        => $type,
                'bowEquipped' => $bowEquipped,
            ])
        </div>
    </div>
@endsection
