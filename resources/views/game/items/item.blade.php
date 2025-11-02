@extends('layouts.app')

@section('content')
  <div class="w-full  mx-auto {{ $item->type !== 'quest' ? 'md:w-2/3' : 'px-4' }}">
    @include('game.items.components.item-layout', ['item' => $item])
  </div>
@endsection
