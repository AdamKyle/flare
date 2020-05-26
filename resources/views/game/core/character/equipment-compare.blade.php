@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Equipped</h4>
                    <hr />

                    @if (empty($details))
                        <div class="alert alert-info">
                            You have nothing equipped for this item type. Anything is better then nothing.
                        </div>
                    @else
                        @foreach($details as $key => $value)
                            @if (!empty($details[$key]))
                                @if ($details[$key]['is_better'])
                                    @include('game.core.partials.item-details-replace', [
                                        'details' => $details[$key]
                                    ])
                                @else
                                    <div class="alert alert-warning">Your current equipment may be better. Check the equip options.</div>
                                @endif
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">To Equip:</h4>
                    <hr />
                    @include('game.core.partials.item-details-to-equip', [
                        'item' => $itemToEquip
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
