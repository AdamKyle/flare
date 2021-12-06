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
            </x-core.alerts.info-alert>

            <div id="sell-items-on-market">
        </div>
    </div>
@endsection
