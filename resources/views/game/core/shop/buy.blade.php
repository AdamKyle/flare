@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div id="shop">
                <p>
                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#weapons" aria-expanded="false" aria-controls="weapons">
                        View Weapons
                    </button>
                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#armour" aria-expanded="false" aria-controls="armour">
                        View Armour
                    </button>
                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#artifacts" aria-expanded="false" aria-controls="artifacts">
                        View Artifacts
                    </button>
                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#spells" aria-expanded="false" aria-controls="spells">
                        View Spells
                    </button>
                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#spells" aria-expanded="false" aria-controls="spells">
                        View Rings
                    </button>
                </p>
                <div class="collapse" id="weapons">
                    @include('game.core.shop.partials.buy.weapons', ['weapons' => $weapons])
                </div>
                <div class="collapse" id="armour">
                    @include('game.core.shop.partials.buy.armour', ['armour' => $armour])
                </div>
                <div class="collapse" id="artifacts">
                    @include('game.core.shop.partials.buy.artifacts', ['artifacts' => $artifacts])
                </div>
                <div class="collapse" id="spells">
                    @include('game.core.shop.partials.buy.spells', ['spells' => $spells])
                </div>
                <div class="collapse" id="rings">
                    @include('game.core.shop.partials.buy.rings', ['rings' => $rings])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
