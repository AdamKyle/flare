@extends('layouts.app')

@section('content')
  @include('game.character.equipment', [
        'isShop' => true,
  ])
@endsection
