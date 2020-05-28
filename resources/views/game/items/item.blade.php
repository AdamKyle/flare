@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{$item->name}}</h4>
                    @include('game.items.partials.item-details', ['item' => $item])
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection