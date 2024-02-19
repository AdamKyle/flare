@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 mx-auto pb-10">
        <x-core.page-title
            title="Selling"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <x-core.cards.card>
            @if ($isLocation)
                <p class="mb-4">
                    You enter the old and musty shop. Along the walls you and see various weapons, armor
                    and other types of items that might benefit you on your journeys.
                </p>
                <p class="mb-4">
                    Counting your gold, you walk in with confidence, knowing you will walk out with
                    better gear. Knowing... your enemies stand no chance.
                </p>
                <p class="mb-4">
                    As you enter, you see an old man behind a worn counter. He smiles warmly at you. Welcoming you:
                </p>

                <p><strong>Shop Keeper</strong>: <em>Hello! welcome! what can I get for you?</em></p>
            @else
                <p class="mb-4">On your journey you come across a merchant on the road. He is carrying his bag full of trinkets and goodies.</p>
                <p class="mb-4">As you approach, he takes off his backpack and warmly greets you:</p>
                <p class="mb-4"><strong>Shop Keeper</strong>: <em>These roads are dangerous my friend! What can I get you?</em></p>
            @endif
        </x-core.cards.card>

        <x-core.cards.card css="mt-5 mb-5">
            <div class="flex items-center relative">
                <p><strong>Your Gold</strong>: <span class="color-gold">{{number_format($gold)}}</span></p>
                <div class="absolute right-[10px]">
                    <form method="post" action="{{route('game.shop.sell.all', ['character' => $character->id])}}">
                        @csrf

                        <x-core.buttons.primary-button type="submit">
                            Sell All Items
                        </x-core.buttons.primary-button>
                    </form>
                </div>
            </div>
        </x-core.cards.card>

        <h4 class="mb-4">Your Items To Sell</h4>

{{--        @livewire('character.inventory.character-inventory')--}}
    </div>
@endsection
