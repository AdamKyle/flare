@extends('layouts.game')

@section('content')
  <div
    id="game-launcher"
    data-show-intro-page="{{ $user->show_intro_page }}"
  ></div>
@endsection
