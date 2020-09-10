@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                        <h4 class="card-title float-left"><x-item-display-color :item="$item" /></h4>
                        <a href="{{url()->previous()}}" class="btn btn-primary float-right">Back</a>
                    </div>
                    <hr>
                    @include('game.items.partials.item-details', ['item' => $item])
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection