@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Market"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>
    <div class="row">
        <div class="col-md-12">
            <div id="market"></div>
        </div>
    </div>
@endsection
