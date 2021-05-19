@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Market"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>
    <div class="mb-2 alert alert-info">
        You can click on the row in the table to open the modal to buy or browse.
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="market"></div>
        </div>
    </div>
@endsection
