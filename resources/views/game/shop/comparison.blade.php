@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>

        @php
            $positions = [
                'weapon'   => ['left-hand','right-hand'],
                'ring'     => ['ring-one','ring-two'],
                'spell'    => ['spell-one','spell-two'],
                'artifact' => ['artifact-one','artifact-two'],
            ];
        @endphp

        @include('game.shop.components.to-equip', ['item' => $itemToEquip, 'isShop' => true])

        <x-core.cards.card-with-title title="Comparison Data">
            @include('game.shop.components.comparison-details', ['details' => $details])
        </x-core.cards.card-with-title>

        <div class="w-full md:w-3/5 m-auto mt-5">
            <x-core.cards.card-with-title title="Buy and Replace">
                <p class="mb-4">If the item you are replacing is in a set, that item will be moved to your inventory.
                    If you do not have inventory space, your purchase will fail (you won't lose gold).</p>
                <p>

                <p class="mb-4 text-orange-700 dark:text-orange-500">
                    <strong>Cost: </strong> {{number_format($itemToEquip['cost'])}} Gold
                </p>
                @if (in_array($itemToEquip['type'], ['stave', 'bow', 'hammer']))
                    <x-core.alerts.info-alert title="Attn!">
                        It does not matter which hand you pick as this item is a duel wielded weapon. This weapon can be used with
                        Attack and Cast and Cast and Attack.
                    </x-core.alerts.info-alert>
                @endif
                @if (in_array($itemToEquip['type'], ['weapon', 'stave', 'bow', 'shield', 'hammer']))
                    <div class="w-full mt-4">
                        <div class="flex justify-center">
                            <form method="post" action="{{route('game.shop.buy-and-replace', ['character' => auth()->user()->character->id])}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="left-hand" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                <x-core.buttons.primary-button>
                                    {{ucfirst(str_replace('-', ' ', $positions[$itemToEquip['type']][0]))}}
                                </x-core.buttons.primary-button>
                            </form>
                            <form method="post" action="{{route('game.shop.buy-and-replace', ['character' => auth()->user()->character->id])}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="right-hand" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                <x-core.buttons.primary-button>
                                    {{ucfirst(str_replace('-', ' ', $positions[$itemToEquip['type']][1]))}}
                                </x-core.buttons.primary-button>
                            </form>
                        </div>
                    </div>
                @else
                    <p class="mb-4">
                        This item has a default position of: {{$itemToEquip['default_position']}}. You cannot select the position.
                    </p>
                    <form method="post" action="{{route('game.shop.buy-and-replace', ['character' => auth()->user()->character->id])}}" class="mr-5 text-center">
                        @csrf
                        <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                        <input type="hidden" value="{{$slotId}}" name="slot_id" />
                        <input type="hidden" value="{{$itemToEquip['default_position']}}" name="position" />
                        <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                        <x-core.buttons.primary-button>
                            Equip
                        </x-core.buttons.primary-button>
                    </form>
                @endif
            </x-core.cards.card-with-title>
        </div>
    </x-core.layout.info-container>
@endsection
