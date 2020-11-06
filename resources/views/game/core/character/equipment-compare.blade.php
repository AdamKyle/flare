@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Item Comparison</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @if (empty($details))
                <div class="alert alert-info">
                    You have nothing equipped for this item type. Anything is better then nothing.
                </div>
            @else
                @foreach($details as $key => $value)
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Equipped: <x-item-display-color :item="$value['slot']->item" /></h4>
                            @if (!empty($details[$key]))
                                @include('game.core.partials.currently-equipped', [
                                    'details' => $details[$key]
                                ])
                                <h6>Stat Details</h6>
                                @include('game.core.partials.equip.details.item-stat-details', ['item' => $details[$key]['slot']->item])
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">To Equip: <x-item-display-color :item="$itemToEquip" /> </h4>
                    <hr />
                    @include('game.core.partials.item-details-to-equip', [
                        'item'         => $itemToEquip,
                        'details'      => $details,
                    ])

                    <form class="mt-4" action="{{route('game.equip.item')}}" method="POST">
                        @csrf
                        @include('game.core.partials.equip.' . $type, [
                            'slotId'      => $slotId,
                            'details'     => $details,
                            'itemToEquip' => $itemToEquip,
                            'type'        => $type
                        ])
                        <button type="submit" class="btn btn-primary">Equip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
