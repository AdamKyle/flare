@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
            title="Selling"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <x-core.cards.card>
            @if ($isLocation)
                <p>
                    You enter the old and musty shop. Along the walls you an see various weapons, armor
                    and other types of items that might benefit you on your journeys.
                </p>
                <p>
                    Counting your gold, you walk in with confidence, knowing you will walk out with
                    better gear. Knowing ... Your enemies stand no chance.
                </p>
                <p>
                    As you enter, you see an old man behind a worn counter. He smiles warmly at you. Welcoming you:
                </p>

                <p><strong>Shop Keeper</strong>: <em>Hello! welcome! what can I get for you?</em></p>
            @else
                <p>On your journey you come across a merchant on the road. He is carrying his bag full of trinkets and goodies.</p>
                <p>As you approach, he takes off his backpack and warmly greets you:</p>
                <p><strong>Shop Keeper</strong>: <em>These roads are dangerous my friend! What can I get you?</em></p>
            @endif
        </x-core.cards.card>

        <x-core.cards.card css="tw-mt-5 tw-mb-5">
            <p><strong>Your Gold</strong>: <span class="color-gold">{{number_format($gold)}}</span></p>
        </x-core.cards.card>

        <h4>Your Items To Sell</h4>
        <x-core.alerts.warning-alert>
            <p>
                You can click "Sell all" (beside the page drop down) to auto sell all items or select items you want
                to sell and click the "Sell all selected" on the table
                to sell just those specific items.
            </p>
            <p>
                <strong>Note:</strong> There is a 5% tax applied to all items you sell.
            </p>
        </x-core.alerts.warning-alert>
        @livewire('character.inventory.data-table', [
            'batchSell'             => true,
            'craftOnly'             => false,
            'isShopSelling'         => true,
            'character'             => $character,
            'showSkillInfo'         => false,
            'showOtherCurrencyCost' => false
        ])
    </div>
@endsection
