@extends('layouts.app')

@section('content')
    <x-core.cards.card-with-title>
      Content here
    </x-core.cards.card-with-title>
    @dump($adventureLog)
@endsection
