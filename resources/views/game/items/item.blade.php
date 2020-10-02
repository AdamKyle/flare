@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2"><x-item-display-color :item="$item" /></h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h4>Item Details</h4>
            <div class="card">
                <div class="card-body">
                    @include('game.items.partials.item-details', ['item' => $item])
                    @guest
                    @else
                        @if (auth()->user()->hasRole('Admin'))
                            <a href="{{route('items.edit', [
                                'item' => $item
                            ])}}" class="btn btn-primary mt-3">Edit Item</a>
                        @endif
                    @endguest
                </div>
            </div>

            <h4>Item Affixes</h4>
            <div class="card">
                <div class="card-body">
                    @include('game.items.partials.item-affixes', ['item' => $item])
                </div>
            </div>

            <h4>Base Equip Stats</h4>
            <div class="card">
                <div class="card-body">
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection