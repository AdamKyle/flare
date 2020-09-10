@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Character Inventory</h4>
                    <hr />

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Equiped Items</h4>
                            <hr />
                                <div class="clearfix">
                                    <div class="float-left">
                                        <span class=""><strong>Max Attack: </strong> {{$characterInfo['maxAttack']}} /</span>
                                        <span class="ml-1"><strong>Max Defence: </strong> {{$characterInfo['maxDefence']}} /</span>
                                        <span class="ml-1"><strong>Max Heal For: </strong> {{$characterInfo['maxHeal']}}</span>
                                    </div>
                                    <div class="float-right">
                                        <a class="btn btn-danger btn-sm" href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('unequip-all').submit();"
                                        >
                                            Unequip All
                                        </a>
        
                                        <form id="unequip-all" action="{{ route('game.unequip.all') }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                            <hr />
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Damage</th>
                                        <th>AC</th>
                                        <th>Type</th>
                                        <th>Position</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($equipped as $equippedItem)
                                        <tr>
                                            <td><a href="{{route('items.item', ['item' => $equippedItem->item->id])}}">{{$equippedItem->item->name}}</a></td>
                                            <td>{{$equippedItem->item->getTotalDamage()}}</td>
                                            <td>{{$equippedItem->item->getTotalDefence()}}</td>
                                            <td>{{$equippedItem->item->type}}</td>
                                            <td>{{$equippedItem->position}}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Inventory</h6>
                            @include('game.core.partials.inventory', [
                                'inventory' => $inventory,
                                'actions'   => 'manage',
                            ])
                        </div>

                        <div class="col-md-6">
                            <h6>Quest Items</h6>
                            @if ($questItems->isEmpty())
                                <div class="alert alert-info">You have no quest items.</div>
                            @else
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Effect</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach($questItems as $questItem)
                                        <tr>
                                            <td><a href="{{route('items.item', ['item' => $questItem->item->id])}}">{{$questItem->item->name}}</a></td>
                                            <td>
                                                @switch($questItem->item->effect)
                                                    @case('walk-on-water')
                                                        Walk On Water
                                                        @break
                                                    @default
                                                        N/A
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</div>
@endsection
