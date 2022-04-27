@extends('layouts.app')

@section('content')
    <div @if($item->type !== 'quest') class="max-w-7xl p-10 m-auto" @endif>
        @include('game.items.components.item-data', ['item' => $item])
    </div>
@endsection
