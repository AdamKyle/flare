@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-core.page-title
                title="Sell items on market board"
                route="{{route('game')}}"
                link="Game"
                color="primary"
            ></x-core.page-title>

            <x-core.alerts.info-alert title="ATTN!">
                <p>
                    You can only sell enchanted items or <a href="/information/usable-items">alchemical items</a> on the market board.
                </p>
                <p>
                    If you are seeing no items show up in the table, chances are you have no enchanted gear
                    or alchemical items to sell.
                </p>
                <p>Unique (<a href="/information/random-enchantments">Random Enchantment Items (AKA Green Items)</a>) already have a defined minimum price. Failure to list it for the minimum price
                will not allow you to sell the item. Uniques are easy for new players to earn and more expensive ones take time and effort to get, you can't just give these away.</p>
            </x-core.alerts.info-alert>

            <div id="sell-items-on-market">
        </div>
    </div>
@endsection
