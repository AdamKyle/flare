@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 mx-auto pb-10">

        <x-core.page-title
            title="Buying"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <div class="m-auto">
            <x-core.cards.card>
                @if ($character->classType()->isMerchant())
                    <x-core.alerts.info-alert>
                        Your class grants you a 25% cost reduction at the shop. The items below have been adjusted in
                        terms of cost.
                    </x-core.alerts.info-alert>
                @endif
                @if ($isLocation)
                    <p class="mb-4 italic">
                        You enter the old and musty shop. Along the walls you and see various weapons, armor
                        and other types of items that might benefit you on your journeys. You see an old man behind the counter writing something in a book,
                        he looks up at you.
                    </p>

                    <p class="mb-4"><strong>Shop Keeper</strong>: <em>Hello! welcome! what can I get for you?</em></p>
                @else
                    <p class="mb-4 italic">On your journey you come across a merchant on the road. He is carrying his bag full of trinkets and goodies.</p>
                    <p class="mb-4 italic">As you approach, he takes off his backpack and warmly greets you:</p>
                    <p class="mb-4"><strong>Shop Keeper</strong>: <em>These roads are dangerous my friend! What can I get you?</em></p>
                @endif
            </x-core.cards.card>

            <div id="player-shop"></div>
        </div>
  </div>
@endsection
