@extends('layouts.app')

@section('content')
  @include('game.adventures.partials.completed-adventure-details', ['adventureLog' => $adventureLog, 'character' => $character])
@endsection
