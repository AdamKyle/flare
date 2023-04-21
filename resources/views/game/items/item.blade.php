@extends('layouts.app')

@section('content')
    <div @if($item->type !== 'quest') class="w-full md:w-[75%] mx-auto" @endif>
        @include('game.items.components.item-layout', ['item' => $item])
    </div>
@endsection
