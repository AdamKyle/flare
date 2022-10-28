@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>

        <x-core.cards.card-with-title title="Purchase multiple of {{$itemName}}" backUrl="{{url()->previous()}}" buttons="true">
            <dl class="my-4">
                <dt>Your Gold:</dt>
                <dd>{{number_format($gold)}}</dd>
                <dt>Item Cost:</dt>
                <dd>{{number_format($cost)}}</dd>
            </dl>

            <form method="post" action="{{route('game.shop.purchase.multiple', ['character' => $characterId])}}">
                @csrf

                <input type="hidden" name="item_id" value="{{$itemId}}" />

                <div class="mb-5" x-data="{
                    amount: 0,
                    get cost() {
                        return this.amount * {{$cost}}
                    },
                    get isDisabled() { return true; }
                }">
                    <label class="label block mb-2" for="amount">Amount</label>
                    <input  x-model="amount" id="amount" type="number" class="form-control" name="amount" min="0" max="75" value="{{ old('amount') }}" required autofocus>
                    @error('amount')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                    <dl class="my-4">
                        <dt>Cost</dt>
                        <dd x-text="cost" :class="{'text-red-500': cost > {{$gold}}}"></dd>
                    </dl>
                </div>

                <x-core.buttons.primary-button type="submit">
                    Purchase Amount
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>

    </x-core.layout.info-container>
@endsection
