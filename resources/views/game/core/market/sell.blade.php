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

            <div id="sell-items-on-market">
        </div>
    </div>
@endsection
