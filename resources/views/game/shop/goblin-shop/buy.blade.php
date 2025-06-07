@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 mx-auto pb-10">
        <x-core.page-title
            title="Goblin Shack (Buying)"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <div class="m-auto">
            <x-core.cards.card>
                @if ($isLocation)
                    <p class="mb-4 italic">
                        You enter into the old shack, it smells musty and the
                        dust on the shelves is thick. The shadows on the walls
                        dance under the faint light of flickering lamps and
                        candles. A goblin, old and shabby comes walking out from
                        the shadows.
                    </p>

                    <p class="mb-4">
                        <strong>Shop Keeper</strong>
                        :
                        <em>Hello! welcome! what can I get for you?</em>
                    </p>
                @else
                    <p class="mb-4 italic">
                        On your journey you come across a Goblin merchant on the
                        road. He is carrying his bag full of trinkets and
                        goodies.
                    </p>
                    <p class="mb-4 italic">
                        As you approach, he takes off his backpack and warmly
                        greets you:
                    </p>
                    <p class="mb-4">
                        <strong>Shop Keeper</strong>
                        :
                        <em>
                            These roads are dangerous my friend! What can I get
                            you?
                        </em>
                    </p>
                @endif
            </x-core.cards.card>

            <x-core.cards.card css="mt-5">
                <p>
                    <strong>Your Gold Bars</strong>
                    :
                    <span class="color-gold">
                        {{ number_format($goldBars) }}
                    </span>
                </p>
                <p class="my-4">
                    Gold bars are gained by you owning one or more kingdoms. You
                    will also need to unlock the Goblin Bank passive to deposit
                    what are called Gold Bars, which cost 2 billion per bar and
                    allow you to hold 1,000 bars per kingdom.
                </p>
                <p class="my-4">
                    The price of each item, when purchased, is split evenly over
                    all kingdoms whose gold bars are equal to or greater than
                    the item cost. it is split such that, at least 1 gold bar,
                    is subtracted from the all kingdoms applicable to the
                    calculation.
                </p>
            </x-core.cards.card>

            @livewire('game.shops.goblin-shop', ['isShop' => true])
        </div>
    </div>
@endsection
