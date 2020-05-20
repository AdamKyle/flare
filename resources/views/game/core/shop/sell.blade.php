@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Inventory</h4>

                    @include('game.core.partials.inventory', [
                        'inventory' => $inventory,
                        'actions'   => 'sell',
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
