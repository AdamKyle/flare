@extends('layouts.app')

@section('content')
    <x-core.page-title-slot 
        route="{{url()->previous()}}"
        link="Back"
        color="success"
    >
        <x-item-display-color :item="$item" />
    </x-core.page-title-slot>
    <hr />
    @include('game.items.partials.item', [
        'item' => $item
    ])
@endsection