@extends('layouts.app')

@section('content')
    <x-core.page-title-slot 
        route="{{route('game.current-listings', [
            'character' => auth()->user()->character->id
        ])}}"
        link="Back"
        color="success"
    >
        <x-item-display-color :item="$marketBoard->item" />
    </x-core.page-title-slot>
    <div class="row">
        <div class="col-md-12">
            <form class="mb-3 mb-2" method="POST" action="{{route('game.update.current-listing', [
                'marketBoard' => $marketBoard->id
            ])}}">
                @csrf

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="market-listed-price">Sell For: </label>
                            <input type="number" class="form-control required" id="market-listed-price" name="listed_price" value={{$marketBoard->listed_price}}> 
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Update Pricing</a>
                    </div>
                </div>
            </form>
            <hr />
            <h4>Market Details</h4>
            @livewire('market.item-board', [
                'itemId' => $marketBoard->item->id
            ])
            <hr />
            @include('game.items.partials.item', [
                'item' => $marketBoard->item
            ])
        </div>
    </div>
@endsection
