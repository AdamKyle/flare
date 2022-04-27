@extends('layouts.app')

@section('content')
    @include('game.items.components.item-data', ['item' => $item])
@endsection
