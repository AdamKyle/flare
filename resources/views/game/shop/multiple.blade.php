@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Purchase multiple of {{$itemName}}"
            backUrl="{{url()->previous()}}"
            buttons="true"
        >
            @if ($character->classType()->isMerchant())
                <x-core.alerts.info-alert>
                    Your class adjusts the total cost of the amount of items you
                    purchase by 25%. That is if you buy 10 items, instead of
                    each one having their cost reduced by 25%, your total cost
                    is reduced by 25%.
                </x-core.alerts.info-alert>
            @endif

            <dl class="my-4">
                <dt>Your Gold:</dt>
                <dd>{{ number_format($gold) }}</dd>
                <dt>Item Cost:</dt>
                <dd>{{ number_format($cost) }}</dd>
            </dl>

            <form
                x-data="{ quantity: 1, cost: @json($cost), gold: @json($gold) }"
                x-init="quantity = 1"
                method="post"
                action="{{ route('game.shop.purchase.multiple', ['character' => $characterId]) }}"
            >
                @csrf()

                <input
                    type="hidden"
                    value="{{ $itemId }}"
                    name="item_id"
                    id="item_id"
                />

                <div class="my-4" x-cloak>
                    <label for="quantity">Enter Quantity:</label>
                    <input
                        x-model="quantity"
                        type="number"
                        id="amount"
                        name="amount"
                        min="1"
                        max="75"
                        class="w-1/3 border rounded-md p-2"
                    />
                </div>

                <div x-show="quantity > 75" x-cloak>
                    <x-core.alerts.danger-alert>
                        Quantity cannot exceed 75.
                    </x-core.alerts.danger-alert>
                </div>

                <div x-show="quantity * cost > gold" x-cloak>
                    <x-core.alerts.danger-alert>
                        You don't have enough gold to purchase that many items.
                    </x-core.alerts.danger-alert>
                </div>

                <div
                    class="my-4 text-green-700 dark:text-green-500"
                    x-cloak
                    x-show="quantity <= 75 && quantity * cost <= gold"
                >
                    <p
                        x-text="
                            'You want to purchase ' +
                                quantity +
                                ', that will cost you ' +
                                (quantity * cost).toLocaleString() +
                                '.'
                        "
                    ></p>
                </div>

                <x-core.buttons.primary-button
                    x-bind:disabled="quantity > 75 || quantity * cost > gold"
                    type="submit"
                >
                    Purchase Amount
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
