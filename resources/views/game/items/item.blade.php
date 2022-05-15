@extends('layouts.app')

@section('content')
    <div @if($item->type !== 'quest') class="min-width-full md:min-w-[75%] m-auto" @endif>
        @include('game.items.components.item-layout', ['item' => $item])
    </div>
@endsection
