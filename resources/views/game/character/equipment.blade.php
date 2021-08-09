@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Item Comparison"
        route="{{url()->previous()}}"
        link="Back"
        color="success"
    ></x-core.page-title>
    <hr />
    @if ($setEquipped)
        <div class="alert alert-warning mt-2 mb-3">
            Equipping this item while <strong>Set {{$setIndex}}</strong> is equipped will remove the set and equip this item.
            You <strong>cannot</strong> mix and match sets nor sets with other equipment.
        </div>
    @endif
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
