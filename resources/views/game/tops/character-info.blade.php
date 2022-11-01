@extends('layouts.app')

@section('content')
    <div class="w-full ml-auto mr-auto">
        <x-core.page-title
            title="{{$character->name}}"
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        ></x-core.page-title>

        <x-core.cards.card>
            <div class="grid lg:grid-cols-2 gap-2">
                <div class="my-4">
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$character->name}}</dd>
                        <dt>Level:</dt>
                        <dd>{{number_format($character->level)}}</dd>
                        <dt>Race:</dt>
                        <dd>{{$character->race->name}}</dd>
                        <dt>Class:</dt>
                        <dd>{{$character->class->name}}</dd>
                    </dl>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Gold:</dt>
                        <dd>{{number_format($character->gold)}}</dd>
                        <dt>Crystal Shards:</dt>
                        <dd>{{number_format($character->shards)}}</dd>
                        <dt>Copper Coins:</dt>
                        <dd>{{number_format($character->copper_coins)}}</dd>
                        <dt>Gold Dust:</dt>
                        <dd>{{number_format($character->gold_dust)}}</dd>
                    </dl>
                </div>
                <div>
                    <h2 className="my-2">Equipped Items</h2>
                    @livewire('game.tops.character-equipped-inventory', [
                        'characterId' => $character->id
                    ])
                </div>
            </div>

        </x-core.cards.card>
    </div>
@endsection
