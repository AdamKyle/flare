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
                        <dd>{{ $character->name }}</dd>
                        <dt>Level:</dt>
                        <dd>{{ number_format($character->level) }}</dd>
                        <dt>Race:</dt>
                        <dd>{{ $character->race->name }}</dd>
                        <dt>Class:</dt>
                        <dd>{{ $character->class->name }}</dd>
                    </dl>
                    <div
                        class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                    ></div>
                    <dl>
                        <dt>Gold:</dt>
                        <dd>{{ number_format($character->gold) }}</dd>
                        <dt>Crystal Shards:</dt>
                        <dd>{{ number_format($character->shards) }}</dd>
                        <dt>Copper Coins:</dt>
                        <dd>{{ number_format($character->copper_coins) }}</dd>
                        <dt>Gold Dust:</dt>
                        <dd>{{ number_format($character->gold_dust) }}</dd>
                    </dl>
                    <div
                        class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                    ></div>
                    <div class="grid lg:grid-cols-2 gap-2">
                        <div>
                            <h3 class="my-2">Reincarnation Information</h3>
                            <p class="mb-4">
                                Below shows the amount of times this character
                                has reincarnated as well as relative stat
                                information.
                            </p>
                            <dl>
                                <dt>Times Character Reincarnated:</dt>
                                <dd>
                                    {{ number_format(is_null($character->times_reincarnated) ? 0 : $character->times_reincarnated) }}
                                </dd>
                                <dt>Stats To Carry Over:</dt>
                                <dd>
                                    {{ number_format(is_null($character->reincarnated_stat_increase) ? 0 : $character->reincarnated_stat_increase) }}
                                </dd>
                                <dt>Xp penalty:</dt>
                                <dd>
                                    {{ is_null($character->xp_penalty) ? 0 : $character->xp_penalty * 100 }}%
                                </dd>
                            </dl>
                        </div>
                        <div
                            class="block lg:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                        ></div>
                        <div>
                            <h3 class="my-2">Attack Data</h3>
                            <dl>
                                <dt>Weapon Damage</dt>
                                <dd>
                                    {{ number_format($attackData['attack']['weapon_damage']) }}
                                </dd>
                                <dt>Spell Damage</dt>
                                <dd>
                                    {{ number_format($attackData['cast']['spell_damage']) }}
                                </dd>
                                <dt>Healing</dt>
                                <dd>
                                    {{ number_format($attackData['cast']['heal_for']) }}
                                </dd>
                                <dt>Ring Damage</dt>
                                <dd>
                                    {{ number_format($attackData['attack']['ring_damage']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 className="my-2">Equipped Items</h2>
                    @livewire(
                        'game.tops.character-equipped-inventory',
                        [
                            'characterId' => $character->id,
                        ]
                    )
                </div>
            </div>
        </x-core.cards.card>
    </div>
@endsection
