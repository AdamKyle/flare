@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Weapons</h4>

                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Base Damage</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($weapons as $weapon)
                                        <tr>
                                            <td>{{$weapon->name}}</td>
                                            <td>{{$weapon->base_damage}}</td>
                                            <td>{{$weapon->cost}}</td>
                                            <td>
                                                <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                   onclick="event.preventDefault();
                                                                 document.getElementById('shop-buy-form-weapon-{{$weapon->id}}').submit();">
                                                    {{ __('Buy') }}
                                                </a>

                                                <form id="shop-buy-form-weapon-{{$weapon->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                                    @csrf

                                                    <input type="hidden" name="item_id" value={{$weapon->id}} />
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Armour</h4>

                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($armour as $armour)
                                        <tr>
                                            <td>{{$armour->name}}</td>
                                            <td>{{$armour->type}}</td>
                                            <td>{{$armour->cost}}</td>
                                            <td>
                                                <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                   onclick="event.preventDefault();
                                                                 document.getElementById('shop-buy-form-armour-{{$armour->id}}').submit();">
                                                    {{ __('Buy') }}
                                                </a>

                                                <form id="shop-buy-form-armour-{{$armour->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                                    @csrf

                                                    <input type="hidden" name="item_id" value={{$armour->id}} />
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Artifacts</h4>

                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($artifacts as $artifact)
                                        <tr>
                                            <td>{{$artifact->name}}</td>
                                            <td>{{$artifact->cost}}</td>
                                            <td>
                                                <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                   onclick="event.preventDefault();
                                                                 document.getElementById('shop-buy-form-artifact-{{$armour->id}}').submit();">
                                                    {{ __('Buy') }}
                                                </a>

                                                <form id="shop-buy-form-artifact-{{$armour->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                                    @csrf

                                                    <input type="hidden" name="item_id" value={{$artifact->id}} />
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Spells</h4>

                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Base Damage</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($spells as $spell)
                                        <tr>
                                            <td>{{$spell->name}}</td>
                                            <td>{{!is_null($spell->base_damage) ? $spell->base_damage : 'N/A'}}</td>
                                            <td>{{$spell->cost}}</td>
                                            <td>
                                                <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                   onclick="event.preventDefault();
                                                                 document.getElementById('shop-buy-form-spell-{{$spell->id}}').submit();">
                                                    {{ __('Buy') }}
                                                </a>

                                                <form id="shop-buy-form-spell-{{$spell->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                                    @csrf

                                                    <input type="hidden" name="item_id" value={{$spell->id}} />
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Rings</h4>

                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Base Damage</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($rings as $ring)
                                        <tr>
                                            <td>{{$ring->name}}</td>
                                            <td>{{$ring->base_damage}}</td>
                                            <td>{{$ring->cost}}</td>
                                            <td>
                                                <a class="btn btn-primary" href="{{route('game.shop.buy.item')}}"
                                                   onclick="event.preventDefault();
                                                                 document.getElementById('shop-buy-form-rings-{{$ring->id}}').submit();">
                                                    {{ __('Buy') }}
                                                </a>

                                                <form id="shop-buy-form-rings-{{$ring->id}}" action="{{route('game.shop.buy.item')}}" method="POST" style="display: none;">
                                                    @csrf

                                                    <input type="hidden" name="item_id" value={{$ring->id}} />
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
