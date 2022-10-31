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
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div class="my-2">
                        <x-core.buttons.primary-button data-toggle="collapse" data-target='#attack-details'>View Attack Details</x-core.buttons.primary-button>
                        <div id="attack-details" class="collapse multiple-collapse">
                            <div class="mt-5 p-5">
                                <h2 class="mt-2 font-light">
                                    Attack Details
                                </h2>
                                <div class="my-2 grid lg:grid-cols-2 gap-2">
                                    @foreach ($attackData as $key => $value)
                                        @if (!str_contains($key, 'voided'))
                                            <div class="mb-4">
                                                <h3 class="mb-4">{{ucfirst(str_replace('_', ' ', $key))}}</h3>
                                                <dl class="mb-4">
                                                    @foreach ($value as $dataKey => $data)
                                                        @if ($dataKey !== 'affixes' && $dataKey !== 'name')
                                                            <dt>{{ucfirst(str_replace('_', ' ', $dataKey))}}:</dt>
                                                            <dd>{{is_float($data) ? ($data * 100) . '%' : number_format($data)}}</dd>
                                                        @endif
                                                    @endforeach
                                                </dl>
                                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                                <h3 class="mb-4">Affix Data</h3>
                                                <dl>
                                                    @foreach ($value as $dataKey => $data)
                                                        @if ($dataKey === 'affixes')
                                                            @foreach ($data as $affixKey => $affixData)
                                                                <dt>{{ucfirst(str_replace('_', ' ', $affixKey))}}:</dt>
                                                                <dd>{{is_float($affixData) ? ($affixData * 100) . '%' : number_format($affixData)}}</dd>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </dl>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-core.buttons.primary-button data-toggle="collapse" data-target='#voided-attack-details'>View Voided Attack Details</x-core.buttons.primary-button>
                    <div id="voided-attack-details" class="collapse multiple-collapse">
                        <div class="mt-5 p-5">
                            <h2 class="mt-2 font-light">
                                Voided Attack Details
                            </h2>
                            <div class="my-2 grid lg:grid-cols-2 gap-2">
                                @foreach ($attackData as $key => $value)
                                    @if (str_contains($key, 'voided'))
                                        <div class="mb-4">
                                            <h3 class="mb-4">{{ucfirst(str_replace('_', ' ', $key))}}</h3>
                                            <dl class="mb-4">
                                                @foreach ($value as $dataKey => $data)
                                                    @if ($dataKey !== 'affixes' && $dataKey !== 'name')
                                                        <dt>{{ucfirst(str_replace('_', ' ', $dataKey))}}:</dt>
                                                        <dd>{{is_float($data) ? ($data * 100) . '%' : number_format($data)}}</dd>
                                                    @endif
                                                @endforeach
                                            </dl>
                                            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                            <h3 class="mb-4">Affix Data</h3>
                                            <dl>
                                                @foreach ($value as $dataKey => $data)
                                                    @if ($dataKey === 'affixes')
                                                        @foreach ($data as $affixKey => $affixData)
                                                            <dt>{{ucfirst(str_replace('_', ' ', $affixKey))}}:</dt>
                                                            <dd>{{is_float($affixData) ? ($affixData * 100) . '%' : number_format($affixData)}}</dd>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </dl>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
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
