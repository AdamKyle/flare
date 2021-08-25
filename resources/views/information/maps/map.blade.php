@extends('layouts.information', [
    'pageTitle' => 'Location'
])

@section('content')
    <div class="row page-titles mt-3">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$map->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="mt-3">
        <x-cards.card>
            <div class="text-center">
                <img src="{{$mapUrl}}" width="500" />
            </div>
            @if (!is_null($itemNeeded))
                <h3>Item required for access</h3>
                <hr />
                <p class="mt-3 mb-2">
                    In order to access this plane, you will need to have the following quest item:
                </p>
                <ul>
                    <li>
                        <a href="{{route('game.items.item', ['item' => $itemNeeded])}}">
                            <x-item-display-color :item="$itemNeeded" />
                        </a>
                    </li>
                </ul>
            @endif
            <h3>Monsters</h3>
            <hr />
            @livewire('admin.monsters.data-table', [
                'onlyMapName' => $map->name,
                'withCelestials' => false,
            ])
            <h3>Celestials</h3>
            <hr />
            @livewire('admin.monsters.data-table', [
                'onlyMapName' => $map->name,
                'withCelestials' => true,
            ])
        </x-cards.card>
    </div>
@endsection
