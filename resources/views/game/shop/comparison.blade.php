@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @dump([
            'details'        => $details,
            'slotId'         => $slotId,
            'details'        => $details,
            'itemToEquip'    => $itemToEquip,
            'type'           => $type,
            'bowEquipped'    => $bowEquipped,
            'staveEquipped'  => $staveEquipped,
            'hammerEquipped' => $hammerEquipped,
        ])

        @php
            $positions = [
                'weapon'   => ['left-hand','right-hand'],
                'ring'     => ['ring-one','ring-two'],
                'spell'    => ['spell-one','spell-two'],
                'artifact' => ['artifact-one','artifact-two'],
            ];
        @endphp

        @include('game.shop.components.to-equip', ['item' => $itemToEquip])

        <x-core.cards.card-with-title title="Comparison Data">
            @include('game.shop.components.comparison-details', ['details' => $details])
        </x-core.cards.card-with-title>

        <div class="w-full md:w-3/5 m-auto mt-5">
            <x-core.cards.card-with-title title="Buy and Replace">
                <p>If the item you are replacing is in a set, that item will be moved to your inventory.
                    If you do not have inventory space, your purchase will fail (you won't loose gold).</p>
                @if ($itemToEquip['type'] === 'weapon')
                    <div class="w-full mt-4">
                        <div class="flex justify-center">
                            <form method="post" action="{{route('game.shop.buy-and-replace', ['character' => auth()->user()->character->id])}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="left-hand" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                <x-core.buttons.primary-button>
                                    Left Hand
                                </x-core.buttons.primary-button>
                            </form>
                            <form method="post" action="{{route('game.shop.buy-and-replace', ['character' => auth()->user()->character->id])}}" class="mr-5">
                                @csrf
                                <input type="hidden" value="{{$itemToEquip['type']}}" name="equip_type" />
                                <input type="hidden" value="{{$slotId}}" name="slot_id" />
                                <input type="hidden" value="right-hand" name="position" />
                                <input type="hidden" value="{{$itemToEquip['id']}}" name="item_id_to_buy" />
                                <x-core.buttons.primary-button>
                                    Right Hand
                                </x-core.buttons.primary-button>
                            </form>
                        </div>
                    </div>
                @else
                @endif
            </x-core.cards.card-with-title>
        </div>
    </x-core.layout.info-container>
@endsection
