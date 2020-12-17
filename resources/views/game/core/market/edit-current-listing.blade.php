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

                <div class="alert alert-warning">
                    <p>While you are editing your items listed price, your item will not appear on the market board. Once you are done, the item will be relisted.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="market-listed-price">Sell For: </label>
                            <input type="number" class="form-control required" id="market-listed-price" name="listed_price" value={{$marketBoard->listed_price}}> 
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check move-down-30">
                            <input id="isLocked" class="form-check-input" type="checkbox" data-toggle="toggle" name="is_locked" disabled {{$marketBoard->is_locked ? 'checked' : ''}}>
                            <label for="isLocked" class="form-check-label ml-2">Currently Locked</label>
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
            <div id="market-item-board-{{$marketBoard->item->id}}" data-item-id="{{$marketBoard->item->id}}"></div>
            <hr />
            @include('game.items.partials.item', [
                'item' => $marketBoard->item
            ])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        renderBoard('market-item-board-{{$marketBoard->item->id}}');
    </script>
@endpush
