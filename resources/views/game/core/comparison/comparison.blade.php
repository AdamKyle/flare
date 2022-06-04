@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>

        @php
            $positions = [
                'weapon'          => ['left-hand','right-hand'],
                'stave'           => ['left-hand','right-hand'],
                'hammer'          => ['left-hand','right-hand'],
                'bow'             => ['left-hand','right-hand'],
                'ring'            => ['ring-one','ring-two'],
                'spell-healing'   => ['spell-one','spell-two'],
                'spell-damage'    => ['spell-one','spell-two'],
                'shield'          => ['left-hand', 'right-hand']
            ];

            $details     = $itemToEquip['details'];
            $slotId      = $itemToEquip['slotId'];
            $itemToEquip = $itemToEquip['itemToEquip'];

            $item = \App\Flare\Models\Item::find($itemToEquip['id']);
        @endphp

        <x-core.cards.card-with-title title="Comparison Data">
            @include('game.core.comparison.components.comparison-details', ['details' => $details])
        </x-core.cards.card-with-title>

        <x-core.buttons.primary-button data-toggle="collapse" data-target='#item-details'>View Item Details</x-core.buttons.primary-button>
        <div id="item-details" class="collapse multiple-collapse">
            <div class="mt-5 p-5">
                <h2 class="mt-2 font-light">
                    <x-item-display-color :item="$item" />
                </h2>
                @include('game.items.components.item-details', ['item' => $item])
            </div>
        </div>

        <div class="w-full md:w-3/5 m-auto mt-5">
            <x-core.cards.card-with-title title="Buy and Replace">
                <p class="mb-4">If the item you are replacing is in a set, that item will be moved to your inventory.
                    If you do not have inventory space, your purchase will fail (you won't lose gold).</p>
                <p>

                <p class="mb-4 text-orange-700 dark:text-orange-500">
                    <strong>Cost: </strong>
                    @if (isset($listingPrice))
                        {{number_format($listingPrice * 1.05)}} Gold (includes 5% Tax)
                    @else
                        {{number_format($itemToEquip['shop_cost'])}} Gold
                    @endif
                </p>
                @if (in_array($itemToEquip['type'], ['stave', 'bow', 'hammer']))
                    <x-core.alerts.info-alert title="Attn!">
                        It does not matter which hand you pick as this item is a duel wielded weapon. This weapon can be used with
                        Attack and Cast and Cast and Attack.
                    </x-core.alerts.info-alert>
                @endif
                @if (in_array($itemToEquip['type'], ['weapon', 'stave', 'bow', 'shield', 'hammer', 'ring', 'spell-healing', 'spell-damage', 'shield']))
                    <div class="w-full mt-4">
                        <div class="flex justify-center">
                            <form method="post" action="{{$route}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="{{$positions[$itemToEquip['type']][0]}}" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                @if (isset($listingId))
                                    <input type="hidden" value="{{$listingId}}" name="market_board_id" />
                                @endif
                                <x-core.buttons.primary-button>
                                    {{ucfirst(str_replace('-', ' ', $positions[$itemToEquip['type']][0]))}}
                                </x-core.buttons.primary-button>
                            </form>
                            <form method="post" action="{{$route}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="{{$positions[$itemToEquip['type']][1]}}" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                @if (isset($listingId))
                                    <input type="hidden" value="{{$listingId}}" name="market_board_id" />
                                @endif
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
                    <form method="post" action="{{$route}}" class="mr-5 text-center">
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
